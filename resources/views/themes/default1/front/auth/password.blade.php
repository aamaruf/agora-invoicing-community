@extends('themes.default1.layouts.front.master')
@section('title')
Forgot Paswword | Faveo Helpdesk
@stop
@section('page-heading')
 Forgot Password
@stop
@section('page-header')
Forgot Password
@stop
@section('breadcrumb')
@section('breadcrumb')
    @if(Auth::check())
        <li><a class="text-primary" href="{{url('my-invoices')}}">Home</a></li>
    @else
         <li><a class="text-primary" href="{{url('login')}}">Home</a></li>
    @endif
     <li class="active text-dark">Forgot Password? Reset it Now!</li>
@stop  
@stop
@section('main-class') 
main
@stop
@section('content')
        <div class="container py-4">
            <div id="errorMessage" class="alert alert-success alert-dismissible fade show" style="display: none;max-width: 500px;width: 100%;margin-left: 300px;">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <span id="errorMessageText"></span>
        </div>
         <div id="alertMessage"></div>
        <div id="errorMessage"></div>

            <div class="row justify-content-center">

                <div class="col-md-6 col-lg-6 mb-5 mb-lg-0 pe-5">

                    <p class="text-2">Lost your password?</p>
                        <div class="row">

                            <div class="form-group col">

                                <label class="form-label text-color-dark text-3">E-mail Address <span class="text-color-danger">*</span></label>

                                <input name="email" value="" id="email" class="form-control form-control-lg text-4" required>
                            </div>
                            <h6 id="resetpasswordcheck"></h6>
                        </div>

                        <div class="row justify-content-between">

                            <div class="form-group col-md-auto">

                                <a class="text-decoration-none text-color-primary font-weight-semibold text-2" href="{{url('login')}}">I know my password</a>
                            </div>
                        </div>
                           @if ($status->recaptcha_status == 1 && $apiKeys->nocaptcha_sitekey != '00' && $apiKeys->captcha_secretCheck != '00')
                                {!! NoCaptcha::display(['id' => 'pass-recaptcha-1', 'data-callback' => 'PassonRecaptcha']) !!}
                                <input type="hidden" id="pass-recaptcha-response-1" name="pass-recaptcha-response-1">
                                <div class="pass-verification" id="passcaptcha"></div>
                                <span id="passcaptchacheck"></span><br>
                            @endif

                        <div class="row">

                            <div class="form-group col">

                                <button type="button" class="btn btn-dark btn-modern w-100 text-uppercase font-weight-bold text-3 py-3" data-loading-text="Loading..." name="sendOtp" id="resetmail" onclick="resetpassword()">Send Mail</button>

                            </div>
                        </div>
                </div>
            </div>
        </div>
@stop 
@section('script')

<script>

   var recaptchaValid = false;
        function PassonRecaptcha(response) {
        if (response === '') {
            recaptchaValid = false; // reCAPTCHA validation failed
        } else {
            recaptchaValid = true; // reCAPTCHA validation succeeded
            $('#pass-recaptcha-response-1').val(response);
        }
        }
    
         function PassvalidateRecaptcha() {
                 var recaptchaResponse = $('#pass-recaptcha-response-1').val();

                if (recaptchaResponse === '') {
                    $('#passcaptchacheck').show();
                    $('#passcaptchacheck').html("Robot verification failed, please try again.");
                    $('#passcaptchacheck').focus();
                    $('#passcaptcha').css("border-color", "red");
                    $('#passcaptchacheck').css({"color": "red", "margin-top": "5px"});
                    return false;
                } else {
                    $('#passcaptchacheck').hide();
                    return true;
                }
         }
   $('#email').keyup(function(){
                 verify_mail_check();
                 
            });
           function verify_mail_check(){
              var pattern = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
               var email_val = $('#email').val();
               if(email_val.length == ''){
                $('#resetpasswordcheck').show();
                $('#resetpasswordcheck').html("Please Enter an Email");
                $('#resetpasswordcheck').focus();
                $('#email').css("border-color","red");
                $('#resetpasswordcheck').css({"color":"red","margin-top":"5px"});
                // userErr =false;
                $('html, body').animate({

                    scrollTop: $("#emailcheck").offset().top - 200
                }, 1000)
                return false;
            }
              if(pattern.test($('#email').val())){
                 $('#resetpasswordcheck').hide();
                  $('#email').css("border-color","");
                 return true;
               
              }
              
              else{
                 $('#resetpasswordcheck').show();
                $('#resetpasswordcheck').html("Please Enter a valid email");
                 $('#resetpasswordcheck').focus();
                $('#email').css("border-color","red");
                $('#resetpasswordcheck').css({"color":"red","margin-top":"5px"});

                   // mail_error = false;
                return false;
                
              }

            }

                        function resetpassword() 
                        {  
                            
                           var mail_error = true;
                           var mobile_error = true;
                           $('#resetpasswordcheck').hide();
                                                        
                           if (verify_mail_check() && PassvalidateRecaptcha()) {
                          $("#resetmail").html("<i class='fa fa-circle-o-notch fa-spin fa-1x fa-fw'></i>Sending...");
                                    var data = {
                                        "email":   $('#email').val(),
                                        "pass-recaptcha-response-1":$('#pass-recaptcha-response-1').val(),
                                      
                                    };
                                    $.ajax({
                                        url: '{{url('password/email')}}',
                                        type: 'POST',
                                        data: data,
                                       
                                        success: function (response) {
                                            
                                        if(response.type == 'success'){
                                             var result =  '<div class="alert alert-success alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+response.message+'!</div>';
                                            $('#error').hide(); 
                                            $('#alertMessage').show();
                                            $('#alertMessage').html(result);
                                            // $('#alertMessage2').html(result);
                                            $("#resetmail").html("Send Email");
                                             setTimeout(function() {
                                            location.reload(true);
                                        }, 10000);
                                          
                                              // response.success("Success");
                                           }  else {
                                               
                                             var result =  '<div class="alert alert-warning alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+response.message+'!</div>';
                                            $('#error').hide(); 
                                            $('#alertMessage').show();
                                            $('#alertMessage').html(result);
                                            // $('#alertMessage2').html(result);
                                            $("#resetmail").html("Send Email");
                                             setTimeout(function() {
                                            location.reload(true);
                                        }, 10000);
                                           }
                                        },
                                     error: function(ex) {
                                        var myJSON = JSON.parse(ex.responseText);
                                        var errorMessage = myJSON.result && myJSON.result.length > 0 ? myJSON.result[0] : "An error occurred.";
                                        $('#errorMessageText').text(errorMessage);
                                        $('#errorMessage').show();
                                        $("#resetmail").html("Send Email");
                                         setTimeout(function() {
                                            location.reload(true);
                                        }, 10000);
                                    }
                                    });
                                  }
                                  else{
                                    return false;
                                  }
                                }
                              </script>
@stop
                              