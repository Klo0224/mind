// function LoginPage() {
//     window.location.replace(
//         "Login.php");
// }

function LoginPage() {
    window.location.replace("Login.html");
}
function AdminLogin(event) {
    if (event.altKey && event.shiftKey && event.key === 'L') {
      window.location.replace("MHP_ADMIN/admin.php");
    }
  }

  document.addEventListener('keydown', AdminLogin);