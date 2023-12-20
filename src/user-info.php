<?php

require_once __DIR__ . '/../vendor/autoload.php';

require './models/UserModel.php';
require './models/TodoModel.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (isset($_COOKIE['token'])) {
    session_start();
    $token = $_COOKIE['token'];
    $secret_key = 'secret';

    try {
        $user_information = JWT::decode($token, new Key($secret_key, 'HS256'));

        $user = new UserModel();
        $row = $user->login($user_information);
        $_SESSION['username'] = $row['username'];
        $_SESSION['user_id'] = $user_information->user_id;

        $todo = new TodoModel();
        $todos = $todo->getUserTodos($user_information->user_id);

        $todos_pending = array_filter($todos, function ($todo) {
            return $todo['completed'] == 0;
        });

        $_SESSION['todos'] = $todos;
    } catch (\Throwable $th) {
        echo 'Authentication failed';
        echo $th;
        // header("Location: index.html");
        // exit();
    }
} else {
    header('Location: index.html');
    exit ();
}
?>

  <?php include ('head.html') ?>

    <header class="header">
      <h2>Welcome <?php print $_SESSION['username'] ?>!!!</h2>
      <nav>
        <a href="/logout.php" id="logout-button">Logout</a>
      </nav>
    </header>
    <main class="userContainer">
      <section class="todo-container">
        <form action="/create-todo.php" method="POST" class="todo-form">
          <input type="text" placeholder="Write a new TODO" name="todo" />
          <button type="submit" style="display:none;">Create</button>
        </form>
        <ul>
    <?php
        foreach ($todos as $element):
            $checked = ($element['completed'] == 0) ? '' : 'checked';
    ?>
      <li class='list-todo'>
<input type='checkbox' <?php echo $checked ?> data-todo-id=<?php echo $element['id'] ?> />
          <p><?php echo $element['title'] ?></p>
          <button>X</button>
      </li>
    <?php endforeach; ?>
        </ul>
        <footer class="list-footer">
        <p><span id="todo-count"><?php print count($todos_pending) ?></span> pending tasks</p>
        </footer>
      </section>
    </main>

<script>
  const checkbox = document.querySelectorAll("input[type=checkbox]")

  checkbox.forEach(check => check.addEventListener("click", (e) => {
    const element = e.target
    const todoId = parseInt(element.getAttribute("data-todo-id"))
    const newCompleteValue = element.checked ? 1 : 0

    fetch("update-todo.php", {
      method: "PUT",
      headers: {
        "Content-type": "application/json"
      },
      body: JSON.stringify({
        todoId,
        completed: newCompleteValue
      })
    })
    .then(res => res.json())
    .then(res => {
      if(res.success) alert(res.message)

      const countElement = document.getElementById("todo-count")

      fetch("get-todo-count.php")
      .then(res => res.json())
      .then(res => countElement.innerText = res.pendingCount)
    })

  }))

</script>
  </body>
</html>
