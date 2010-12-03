<?php
$formval = new FormValidator();
$formval->required()->email()->validate('email', 'Email Address');
$formval->required()->minlength(10)->validate('password', 'Password');
$formval->required()->matches('password', 'Password')->validate('password2', 'Password Confirmation');
$formval->required()->callback('captcha', function($value) {
	return ( preg_match('/^\d\s*\+\s*\d$/', trim($value)) && eval ($value) == 10 ) ? TRUE: FALSE;
}, '%s does not add up to 10!')->validate('captcha', 'Captcha');
if ( $formval->has_errors() ) {
   print_r($formval->get_all_errors());
} else {
	// submit the form!
}
?>
