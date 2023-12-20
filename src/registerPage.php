  <?php include ('head.html') ?>
    <main class="loginContainer">
      <form class="loginForm" method="post" action="register.php" id="loginForm">
        <label for="username">
          Username
          <input type="text" name="username" placeholder="Username" required />
        </label>
        <label for="password">
          Password
          <input type="password" name="password" placeholder="Password" required />
        </label>
        <?php
            if (isset($_GET['data']) && $_GET['data'] === 'missing') {
                print ('<span class="error">Data is missing!!!</span>');
            }
        ?>
        <?php
            if (isset($_GET['error']) && $_GET['error'] === 'credentials') {
                print ('<span class="error">Username or password invalid!!!</span>');
            }
        ?>
        <?php
            if (isset($_GET['error']) && $_GET['error'] === 'invalid') {
                print ('<span class="error">Username invalid!!!</span>');
            }
        ?>
        <button class="loginButton" type="submit">Register</button>
        <a href="/loginPage.php" id="registerAnchor">Have an account?</a>
      </form>
    </main>
  </body>
</html>
