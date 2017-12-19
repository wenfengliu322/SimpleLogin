<div class="form-group">
	<input type="text" name="uname" id="uname" tabindex="1" class="form-control" placeholder="Username" value="">
</div>
<div class="form-group">
	<input type="email" name="email" id="email" tabindex="1" class="form-control" placeholder="Email Address" value="">
</div>
<div class="form-group">
	<input type="password" name="password" id="password2" tabindex="2" class="form-control" placeholder="Password">
</div>
<div class="form-group">
	<input type="password" name="confirm-password" id="confirm-password" tabindex="2" class="form-control" placeholder="Confirm Password">
</div>
<div class="form-group">
	<div class="row">
		<div class="col-sm-6 col-sm-offset-3">
			<input type="button" name="register-submit" id="register-submit" tabindex="4" class="form-control btn btn-register" value="Register Now">
		</div>
	</div>
</div>
<script type="text/javascript">
	$(function() {
		$("#register-submit").click(function(){
			if($("#uname").val() != "" && $("#email").val() != "" && $("#password2").val() != ""){
				if(validateEmail($("#email").val())){
					if($("#password2").val().length < 8){
						alert("Please insert longer password than 8 characters!");
					} else {
						if($("#password2").val() === $("#confirm-password").val()){
							$.ajax({
								method: "POST",
								url: "<?=registerfile?>",
								data: { username: $("#uname").val(), email: $("#email").val(), password: $("#password2").val() }
							}).done(function( msg ) {
								alert(msg);
							});
						}else{
							alert("Passwords do not match!");
						}
					}
				} else {
					alert("Invalid Email!");
				}
			}else{
				alert("Please fill all fields with valid data!");
			}
		});
	});
</script>
