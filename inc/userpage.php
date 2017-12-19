<!DOCTYPE html>
<html lang="en">
<head>
  <title>Bootstrap table</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</head>
<body>

<div class="container">
  <h2>Welcome <?php print $_SESSION['user']['username']; ?></h2>
  <table class="table table-striped">
    <thead>
      <tr>
        <th>Username</th>
        <th>Email</th>
      </tr>
    </thead><tbody>
    <tr>
      <td><?=$_SESSION['user']['username']?></td>
      <td><?=$_SESSION['user']['email']?></td>
    </tr> </tbody>
  </table>
  <p><a href='logout.php'>Logout</a></p>
</div>

</body>
</html>
