<?php
include "config.php";

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $inputLogin = $_POST["Login"];
    $inputPass = $_POST['password'];

    $sql = "SELECT * FROM users WHERE Login = '$inputLogin' AND Password = '$inputPass'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        if ($row = mysqli_fetch_assoc($result)){
            $_SESSION['user_id'] = $row['id_user'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['login'] = $row['Login'];
            $_SESSION['first_name'] = $row['First_Name'];
            $_SESSION['last_name'] = $row['Last_Name'];
            if ($row['role'] == 1) {
                header("location: ../admin/index.php");
            } elseif ($row['role'] == 2) {
                header("location: ../dispatcher.php");
            } elseif ($row['role'] == 3) {
                header("location: ../driver.php");
            } else {
                header("location: ../dashboard.php");
            }
            header("location: ../dashboard.php");
        }
        echo"sdvdf";
    }
    else {
        echo "нет записей";
    }
}
?>