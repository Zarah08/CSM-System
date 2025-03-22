<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.8/umd/popper.min.js"></script>

<script
    type="text/javascript"
    src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.3.0/mdb.min.js"></script>
</body>
<style>
    footer {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
}

footer .col {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  margin-bottom: 20px;
}

footer h4 {
  font-size: 16px;
  margin-bottom: 12px;
  color: #350538;
}

footer p {
  font-size: 13px;
  margin: 0 0 8px 0;
}

footer a {
  font-size: 13px;
  text-decoration: none;
  color: #222;
  margin-bottom: 10px;
}

footer .follow {
  margin-top: 20px;
}

footer .install .row img {
  border: 1px solid #088178;
  border-radius: 6px;
}

footer .install img {
  margin: 10px 0 15px 0;
}

footer .follow i:hover,
footer a:hover {
  color: #0e070e;
}

footer .copyright {
  width: 100%;
  text-align: center;
}
</style>
<footer class="bg-light py-5">
  <div class="container">
    <div class="row">
      <div class="col-md-3 mb-4">
        <img src="/assets/logo.png" alt="Course and Supervisor Matching System Logo" class="rounded-circle mb-3" width="110" height="110">
        <h4 class="text-purple">Contact Us</h4>
        <p class="small mb-1">
          <strong>Address:</strong> Faculty of Computing BUK, Kano Nigeria
        </p>
        <p class="small mb-1"><strong>Phone:</strong> +234801-234-5678</p>
        <p class="small mb-3"><strong>Email:</strong> info@courseandsupervisormatch.edu.ng</p>
        
      </div>

      <div class="col-md-3 mb-4">
        <h4 class="text-purple">Quick Links</h4>
        <ul class="list-unstyled">
          <a href="/about.php" class="text-decoration-none text-secondary">About Us</a>
          <li><a href="/faq.php" class="text-decoration-none text-secondary">FAQ</a></li>
          <li><a href="/contact.php" class="text-decoration-none text-secondary">Contact Us</a></li>
          <li><a href="/privacy-policy.php" class="text-decoration-none text-secondary">Privacy Policy</a></li>
          <li><a href="/terms-of-service.php" class="text-decoration-none text-secondary">Terms of Service</a></li>
        </ul>
      </div>

      <div class="col-md-3 mb-4">
        <h4 class="text-purple">For Students</h4>
        <ul class="list-unstyled">
          <li><a href="/student-signup.php" class="text-decoration-none text-secondary">Sign Up</a></li>
          <li><a href="/student-login.php" class="text-decoration-none text-secondary">Log In</a></li>
          <li><a href="/choose-interest-area.php" class="text-decoration-none text-secondary">Choose Interest Area</a></li>
          <li><a href="/student-profile.php" class="text-decoration-none text-secondary">View Profile</a></li>
          <li><a href="/matching-results.php" class="text-decoration-none text-secondary">Supervisor Matching Results</a></li>
          <li><a href="/student-dashboard.php" class="text-decoration-none text-secondary">Student Dashboard</a></li>
        </ul>
      </div>

      <div class="col-md-3 mb-4">
        <h4 class="text-purple">For Lecturers</h4>
        <ul class="list-unstyled">
          <li><a href="/lecturer-signup.php" class="text-decoration-none text-secondary">Sign Up</a></li>
          <li><a href="/lecturer-login.php" class="text-decoration-none text-secondary">Log In</a></li>
          <li><a href="/choose-expertise.php" class="text-decoration-none text-secondary">Choose Expertise Area(s)</a></li>
          <li><a href="/lecturer-profile.php" class="text-decoration-none text-secondary">View Profile</a></li>
          <li><a href="/course-allocation.php" class="text-decoration-none text-secondary">View Course Allocation</a></li>
          <li><a href="/allocated-students.php" class="text-decoration-none text-secondary">View Allocated Students</a></li>
          <li><a href="/lecturer-dashboard.php" class="text-decoration-none text-secondary">Lecturer Dashboard</a></li>
        </ul>
      </div>
    </div>

    <div class="border-top pt-4 mt-4 text-center">
      <p class="small text-secondary">
        &copy; <?php echo date("Y"); ?> Course and Supervisor Matching System. All Rights Reserved
      </p>
    </div>
  </div>
</footer>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
<!-- Font Awesome for icons -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js"></script>
</html>