function sLogin() {
    var username = document.getElementById("LUsername").value;
    var password = document.getElementById("LPassword").value;
    post("api/login.php", "username="+username+"&password="+password, onLoggedIn);
    return false;
}

function onLoggedIn(json){
    if(json.code < 0) {
        console.error("ERROR: "+json.text);
        /// Shake form
    } else {
        window.location.href = "index.html";
    }
}

function sRegister() {
    var username = document.getElementById("RUsername").value;
    var email = document.getElementById("REmail").value;
    var password = document.getElementById("RPassword").value;
    var password2 = document.getElementById("RPassword2").value;
    if (password !== password2) {
        console.error("ERROR: Password mismatching.")
        // Shake form
    }
    else post("api/sign-up.php", "username="+username+"&email="+email+"&password="+password, onRegistered);
    return false;
}

function onRegistered(json) {
    if(json.code < 0) {
        console.error("ERROR: "+json.text);
        /// Shake form
    } else {
        window.location.href = "index.html";
    }
}