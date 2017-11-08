function submitLogin() {
    var username = document.getElementById("LUsername").value;
    var password = document.getElementById("LPassword").value;
    post("api/login.php", "username="+username+"&password="+password, onLoggedIn);
    return false;
}

function onLoggedIn(json){
}

function submitRegister() {
    return false;
}