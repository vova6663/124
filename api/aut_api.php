<?php
include "config.php";
include "logout.php";

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $inputLogin = $_POST["Login"];
    $inputPass = $_POST['password'];

    $sql = "SELECT * FROM users WHERE Login = '$inputLogin' AND Password = '$inputPass'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        if ($row = mysqli_fetch_assoc($result)){
            if(isset($_SESSION["role"])){
                $_SESSION["role"] = "login";
            }
            $_SESSION['role'] = $row['role'];
            $_SESSION["login"] = $inputLogin;
            $_SESSION['logout'] = true;
            header("location:../index.php");//переделать
        }
        include "../Login.html";//переделать
        exit;
    }
}
?>