<?
if(isset($_SESSION["logout"]) && $_SESSION['logout'] === true)
{
    header("location:../")//Авторизированный пользователь
}
else
{
    header("location:../")//Не авторизированный пользователь
}

?>