const signUpButton=document.getElementById('signUpButton');
const signInButton=document.getElementById('signInButton');
const signInForm=document.getElementById('signIn');
const signUpForm=document.getElementById('signUp');

signUpButton.addEventListener('click',function(){
	signInForm.style.display="none";
	signUpForm.style.display="block";
})
signInButton.addEventListener('click',function(){
	signInForm.style.display="block";
	signUpForm.style.display="none";
})

document.addEventListener('DOMContentLoaded', function() {
    var errorMsg = document.getElementById('error-message');
    if (errorMsg) {
        var inputs = document.querySelectorAll('#signIn input[type="email"], #signIn input[type="password"]');
        inputs.forEach(function(input) {
            input.addEventListener('input', function() {
                errorMsg.style.display = 'none';
            });
        });
    }
});