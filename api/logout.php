<?
if(isset($_SESSION["logout"]) && $_SESSION['logout'] === true)
{
    header("location:../ghost.php")
}
else
{
    header("location:../")//Не авторизированный пользователь
}

?>
