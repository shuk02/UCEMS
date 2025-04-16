// Validate form fields
function validate() {
    var form = document.forms["signup"];
    var name = form["name"].value.trim();
    var password = form["password"].value;
    var repassword = form["repassword"].value;
    var email = form["email"].value.trim();
    var phone = form["phone"].value.trim();
    var realname = form["realname"].value.trim();

    // Check for empty fields
    if (!name || !password || !repassword || !email || !phone || !realname) {
        alert("Please fill all the fields!");
        return false;
    }

    // Validate username length
    if (name.length > 10) {
        alert("Username should be less than 10 characters!");
        return false;
    }

    // Validate password match
    if (password !== repassword) {
        alert("Passwords do not match!");
        return false;
    }

    // Validate phone number length (assuming Malaysian numbers)
    if (!/^\d{10}$/.test(phone)) {
        alert("Please enter a valid 10-digit phone number!");
        return false;
    }

    // Validate university email
    var allowedDomain = "@uptm.edu.my";
    if (!email.endsWith(allowedDomain)) {
        alert("Please use your university email (e.g., example" + allowedDomain + ")");
        return false;
    }

    return true; // Form is valid
}

// Validate username length
function validateUsername() {
    var name = document.forms["signup"]["name"].value.trim();
    if (name.length > 10) {
        alert("Username should be less than 10 characters!");
    }
}

// Validate password match
function validatePassword() {
    var password = document.forms["signup"]["password"].value;
    var repassword = document.forms["signup"]["repassword"].value;
    if (password !== repassword) {
        alert("Passwords do not match!");
    }
}
