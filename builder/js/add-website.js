function checkLogin() {
    /// Loading
    get("api/get-username.php", null, function (json) {
        if (json.code < 0) {
            console.error("ERROR: "+json.text);
            window.location.replace("login.html");
        }
    });
    /// Stop loading
}
checkLogin();

apache = document.getElementById("apache");
nginx = document.getElementById("nginx");
php = document.getElementById("php");
mysql = document.getElementById("mysql");

function radioHandler() {
    if(!apache.checked) {		// you cannot have PHP and/or mySQL without Apache
        php.checked = false;
        mysql.checked = false;
    }
}

function mysqlHandler() {
    if (mysql.checked) {		// Apache with PHP required for mySQL
        apache.checked = true;
        php.checked = true;
    }
}

function phpHandler() {
    if (php.checked) apache.checked = true;		// PHP requires Apache
    else mysql.checked = false;		// you cannot have mySQL without PHP
}

function sWebsite() {
    // Domain
    var domain = encodeURIComponent(document.getElementById("domain").value);
    if(domain === "") {
        console.error("Domain not defined.");
        alert("Please fill the domain field.");
        return false;
    }
    // Webserver type
    var webserver;
    if (apache.checked) webserver = "apache";
    else if (nginx.checked) webserver = "nginx";
    else {
        console.error("Webserver not defined.");
        alert("Please choose a web server.");
        return false;
    }
    // Send request
    var data = "webserver="+webserver+"&php="+php.checked+"&domain="+domain;
    get("api/add-website.php", data, onWebsiteCreated);
    return false;
}

function onWebsiteCreated(json) {
    if (json.code < 0) {
        console.error("ERROR: "+json.text);
        alert("Cannot create website now. Sorry for the inconvenience.");
    } else {
        window.location.href = "index.html";
    }
}