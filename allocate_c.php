<?php
session_start();
include_once('../db_connection.php');

function allocateCourses($conn) {
    // Enable error logging
    ini_set('log_errors', 1);
    ini_set('error_log', '/path/to/error.log');

    try {
        // Fetch lecturers and their expertise
        $lecturerQuery = "SELECT l.id AS lecturer_id, l.name AS lecturer_name, 
                          GROUP_CONCAT(e.name SEPARATOR ', ') AS expertise
                          FROM lecturers l
                          LEFT JOIN lecturer_expertise le ON l.id = le.lecturer_id
                          LEFT JOIN expertise e ON le.expertise_id = e.id
                          GROUP BY l.id";
        $lecturers = $conn->query($lecturerQuery);
        if (!$lecturers) {
            throw new Exception("Error fetching lecturers: " . $conn->error);
        }
        $lecturers = $lecturers->fetch_all(MYSQLI_ASSOC);

        // Fetch courses and their categories
        $courseQuery = "SELECT c.id AS course_id, c.title AS course_title, cat.name AS category 
                        FROM courses c
                        LEFT JOIN categories cat ON c.category_id = cat.id";
        $courses = $conn->query($courseQuery);
        if (!$courses) {
            throw new Exception("Error fetching courses: " . $conn->error);
        }
        $courses = $courses->fetch_all(MYSQLI_ASSOC);

        // Initialize allocation array
        $allocation = [];
        foreach ($lecturers as $lecturer) {
            $allocation[$lecturer['lecturer_id']] = [
                'name' => $lecturer['lecturer_name'],
                'expertise' => explode(', ', strtolower($lecturer['expertise'])),
                'courses' => []
            ];
        }
        $unallocated = [];

        // Calculate the maximum number of courses per lecturer
        $maxCoursesPerLecturer = ceil(count($courses) / count($lecturers));

        // Sort courses: specific categories first, then general
        usort($courses, function($a, $b) {
            if (strtolower($a['category']) === 'general' && strtolower($b['category']) !== 'general') {
                return 1;
            }
            if (strtolower($a['category']) !== 'general' && strtolower($b['category']) === 'general') {
                return -1;
            }
            return 0;
        });

        // Allocate courses using round-robin approach
        $lecturerIds = array_column($lecturers, 'lecturer_id');
        $lecturerIndex = 0;
        $allocatedCount = 0;

        foreach ($courses as $course) {
            $allocated = false;
            $initialIndex = $lecturerIndex;

            do {
                $currentLecturerId = $lecturerIds[$lecturerIndex];
                $currentLecturer = &$allocation[$currentLecturerId];

                if (count($currentLecturer['courses']) < $maxCoursesPerLecturer) {
                    if (strtolower($course['category']) === 'general' || 
                        in_array(strtolower($course['category']), $currentLecturer['expertise'])) {
                        $currentLecturer['courses'][] = $course;
                        $allocated = true;
                        $allocatedCount++;
                        break;
                    }
                }

                $lecturerIndex = ($lecturerIndex + 1) % count($lecturerIds);
            } while ($lecturerIndex !== $initialIndex);

            if (!$allocated) {
                $unallocated[] = $course;
            }
        }

        // Second pass: Try to allocate remaining courses
        foreach ($unallocated as $key => $course) {
            foreach ($lecturerIds as $lecturerId) {
                if (count($allocation[$lecturerId]['courses']) < $maxCoursesPerLecturer) {
                    $allocation[$lecturerId]['courses'][] = $course;
                    unset($unallocated[$key]);
                    $allocatedCount++;
                    break;
                }
            }
        }

        // Store allocation in database
        $conn->begin_transaction();
        try {
            // Clear previous allocations
            $conn->query("DELETE FROM lecturer_course_allocation");

            // Prepare the insert query
            $stmt = $conn->prepare("INSERT INTO lecturer_course_allocation (lecturer_id, course_id) VALUES (?, ?)");

            if (!$stmt) {
                throw new Exception("Error preparing statement: " . $conn->error);
            }

            // Insert allocations
            foreach ($allocation as $lecturerId => $data) {
                foreach ($data['courses'] as $course) {
                    $stmt->bind_param("ii", $lecturerId, $course['course_id']);
                    if (!$stmt->execute()) {
                        throw new Exception("Error inserting allocation: " . $stmt->error);
                    }
                }
            }

            // Commit transaction
            $conn->commit();
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Database error in allocateCourses: " . $e->getMessage());
            echo "An error occurred while saving allocations. Please check the error log.";
            exit();
        }

        // Store results in session
        $_SESSION['allocation'] = $allocation;
        $_SESSION['unallocated'] = array_values($unallocated);
        $_SESSION['stats'] = [
            'total_courses' => count($courses),
            'allocated_courses' => $allocatedCount,
            'unallocated_courses' => count($unallocated),
            'max_courses_per_lecturer' => $maxCoursesPerLecturer
        ];

        header("Location: results_c.php");
        exit();
    } catch (Exception $e) {
        error_log("Error in allocateCourses: " . $e->getMessage());
        echo "An error occurred. Please check the error log for more details.";
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['allocate'])) {
    allocateCourses($conn);
} else {
    echo "Invalid request.";
}
?>
