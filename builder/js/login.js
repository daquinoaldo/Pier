/* LOGIN.HTML */
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

function submitRegister() {
    return false;
}

/* INDEX.HTML */
function checkLogin() {
    /// Loading
    get("api/getUsername.php", null, function (json) {
        if (json.code < 0) {
            console.error("ERROR: "+json.text);
            window.location.replace("login.html");
        } else {
            document.getElementById("welcome_message").innerHTML =
                document.getElementById("welcome_message").innerHTML.replace("!", " "+json.text+"!");
            loadWebsitesList();
        }
    });
}

function loadWebsitesList() {
    get("api/getUserWebsites.php", null, function (json) {
        if (json.code < 0) {    // Not logged in: really unlikely, I just checked
            console.error("ERROR: "+json.text);
            window.location.replace("login.html");
        } else {
            var websites = json.text;
            var table = document.getElementById("websites_table");
            for (var i = 0; i < websites.length; i++) {
                var row = table.insertRow(table.rows.length);
                row.insertCell(0).innerHTML = websites[i].id;
                row.insertCell(1).innerHTML = "<a href=\"http://"+websites[i].domain+"\">"+websites[i].domain+"</a>";
                row.insertCell(2).innerHTML = "<a href=\"#\">manage</a>";
            }
            /// stop loading
        }
    });
}