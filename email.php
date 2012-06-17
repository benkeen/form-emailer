<?php

/*----------------------------------------------------------------------------*\

  email.php
  ---------

  This script enables your website visitors to send you emails directly through 
  your own custom-built webpages. You can specify any fields you wish: all 
  information will be emailed properly. For information on how to integrate it 
  with your webform, see the readme.txt file. 

  Requirements:
    - PHP 4.3 or later
    - PHP has been configured with your SMTP server (for PHP mail() function)


  This script is written by Ben Keen (www.benjaminkeen.com). It is free 
  to distribute, to re-write - to do what ever you want with it. But please read 
  the following disclaimer first: 

  THIS SOFTWARE IS PROVIDED ON AN "AS-IS" BASIS WITHOUT WARRANTY OF ANY KIND. 
  BENJAMINKEEN.COM SPECIFICALLY DISCLAIMS ANY OTHER WARRANTY, EXPRESS OR IMPLIED, 
  INCLUDING ANY WARRANTY OF MERCHANTABILITY OR FITNESS FOR A PARTICULAR PURPOSE. 
  IN NO EVENT SHALL BENJAMINKEEN.COM BE LIABLE FOR ANY CONSEQUENTIAL, INDIRECT, 
  SPECIAL OR INCIDENTAL DAMAGES, EVEN IF BENJAMINKEEN.COM HAS BEEN ADVISED OF 
  THE POSSIBILITY OF SUCH POTENTIAL LOSS OR DAMAGE. USER AGREES TO HOLD 
  BENJAMINKEEN.COM HARMLESS FROM AND AGAINST ANY AND ALL CLAIMS, LOSSES, 
  LIABILITIES AND EXPENSES.

\*----------------------------------------------------------------------------*/

################################################################################
// *** USER SETTINGS ***
// this value lets you specify precisely which domains / servers this script will 
// process forms for. If your server doesn't provide this information, set 
// g_check_sender to false.
$g_check_sender = true;
$g_valid_sites = array("localhost", "yoursite.com");

################################################################################



// program control flow
onload();

function onload()
{
  global $g_check_sender, $g_valid_sites;

  // check required fields
  if     (!isset($_POST['recipient']) || empty($_POST['recipient']))
    display_error("no_recipient");
  elseif (!isset($_POST['redirect_page']) || empty($_POST['redirect_page']))
    display_error("no_redirect_page");

  // check recipient email is valid
  elseif (!is_valid_email($_POST['recipient']))
    display_error("invalid_recipient");


  if ($g_check_sender)
  {
    // get the referer domain and check it's valid
    $referer = get_domain($_SERVER['HTTP_REFERER']);
    $found = false;
    foreach ($g_valid_sites as $site)
    {
      if ($referer == get_domain($site))
        $found = true;
    }
  
    // if the domain wasn't listed, display error message
    if (!$found)
      display_error("domain_not_found");
  } 

  // send the email
  send_email();
}



/*----------------------------------------------------------------------------*\
  Function:    send_email
  Purpose:     extracts the contents of the POST form fields and tries to send 
               an email. 
  Assumptions: "redirect_page" and "recipient" are both contained in $_POST
\*----------------------------------------------------------------------------*/
function send_email()
{
  // prepare search-replace values
  $patterns     = array_keys($_POST);
  $replacements = array_values($_POST);
  array_walk($patterns, "prepare_regexp");
  array_walk($replacements, "encode_chars");

  $to = $_POST['recipient'];   
  unset($_POST['recipient']);

  $redirect_page = $_POST['redirect_page'];
  unset($_POST['redirect_page']);


  // determine email subject
  $email_subject = '';
  if (isset($_POST['email_subject'])) 
  {
    $email_subject = $_POST['email_subject'];   
    $email_subject = preg_replace($patterns, $replacements, $email_subject);
    $email_subject = remove_empty_placeholders($email_subject);
    unset($_POST['email_subject']);
  }


  // build our email header
  $header = "";
  $recipient_header = "";
  if (isset($_POST['email_from'])) 
  {
    $email_from = $_POST['email_from'];
    $email_from = preg_replace($patterns, $replacements, $email_from);
    $email_from = remove_empty_placeholders($email_from);
  
    $header .= "From: $email_from\r\n";
    $recipient_header .= "From: $email_from\r\n";
    unset($_POST['email_from']);
  }

  if (isset($_POST['cc']))
  {
    $header .= "Cc: " . $_POST['cc'];
    unset($_POST['cc']);

    while (list($key, $value) = each($_POST))
    {
      if (preg_match("/^cc(\d+)$/i", $key))
      {
        // if the email is invalid, just ignore it 
        if (is_valid_email($value))
          $header .= ", " . $value;
        unset($_POST[$key]);        
      }
    }
    $header .= "\r\n";
  }


  reset($_POST);
  if (isset($_POST['bcc']))
  {
    $header .= "Bcc: " . $_POST['bcc'];
    unset($_POST['bcc']);

    while (list($key, $value) = each($_POST))
    {
      if (preg_match("/^bcc(\d+)$/i", $key))
      {
        // if the email is invalid, just ignore it 
        if (is_valid_email($value))
          $header .= ", " . $value;
        unset($_POST[$key]);
      }
    }
    $header .= "\r\n";
  }

  if (isset($_POST['reply_to'])) 
  {
    $reply_to = $_POST['reply_to'];
    $reply_to = preg_replace($patterns, $replacements, $reply_to);
    $reply_to = remove_empty_placeholders($reply_to);

    $header .= "Reply-To: $reply_to\r\n";
    unset($_POST['reply_to']);
  }
  
  // assemble email message
  $message = '';

  if (isset($_POST['email_template']) && is_file($_POST['email_template']))
  {
    $template = file_get_contents($_POST['email_template']);    
    $message = preg_replace($patterns, $replacements, $template);
    
    // remove any redundant placeholders
    $message = preg_replace("/%DOLLAR%/", "$", $message);
    $message = preg_replace("/%[^\s]*%/", "", $message);
  }

  // otherwise, no template is defined - just generate an email containing all the content
  else
  {
    reset($_POST);
    while (list($key, $value) = each($_POST))
    {
      $key = preg_replace("/_/", " ", $key);
      $message .= "$key: $value\n";
    }
  }

  // send email
  if (!mail($to, $email_subject, $message, $header))
    display_error("cannot_send_email");


  // if a recipient template has been defined, send that email too.
  if (isset($_POST['receipt_template']) && is_file($_POST['receipt_template']))
  {
    $receipt_to = $_POST[$_POST["receipt_email_field"]];

    // assume that the recipient email field isn't required
    if (!empty($receipt_to))
    {  
      $receipt_email_subject = $_POST['receipt_email_subject'];   
      $receipt_email_subject = preg_replace($patterns, $replacements, $receipt_email_subject);
      $receipt_email_subject = remove_empty_placeholders($receipt_email_subject);
      unset($_POST['receipt_email_subject']);

      $template = file_get_contents($_POST['receipt_template']);        
      $message = preg_replace($patterns, $replacements, $template);

      // remove any redundant placeholders
      $message = preg_replace("/%DOLLAR%/", "$", $message);
      $message = preg_replace("/%[^\s]*%/", "", $message);
    
      if (!mail($receipt_to, $receipt_email_subject, $message, $recipient_header))
        display_error("cannot_send_recipient_email");
    }
  }


  // redirect user to appropriate page
  header("Location: $redirect_page");
  exit;
}



/*----------------------------------------------------------------------------*\
  Function: get_domain
  Purpose:  helper function to strip everything but the domain (and any 
            subdomains for a given URL.
            e.g. http://www.whatever.com/text.html -> whatever.com
                 www.sub.mysite.com/text.html      -> sub.mysite.com
\*----------------------------------------------------------------------------*/
function get_domain($string)
{
  $clean = preg_replace("/^https?:\/\/(www\.)?/", "", $string);
  $clean = preg_replace("/\/.*$/", "", $clean);

  return $clean;
}


/*----------------------------------------------------------------------------*\
  Function: is_valid_email
  Purpose:  checks to make sure an email address is valid.
\*----------------------------------------------------------------------------*/
function is_valid_email($email)
{        
  return eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $email);
}


/*----------------------------------------------------------------------------*\
  Function: prepare_regexp
  Purpose:  helper function to convert a string for preparation as being searched
            for in a regular expression.
\*----------------------------------------------------------------------------*/
function prepare_regexp(&$item, $key)
{
  $item = "/%$item%/";
}

/*----------------------------------------------------------------------------*\
  Function: encode_chars
  Purpose:  helper function to encode $ characters so they're not lost
\*----------------------------------------------------------------------------*/
function encode_chars(&$item, $key)
{
  $item = preg_replace("/\\$/", "%DOLLAR%", $item);
}


/*----------------------------------------------------------------------------*\
  Function: remove_empty_placeholders
  Purpose:  helper function to remove all the placeholder that weren't found
            and replaced in a template - or a string.
  Bug!:     This will cause problems...
\*----------------------------------------------------------------------------*/
function remove_empty_placeholders($string)
{
  return $string;
}


/*----------------------------------------------------------------------------*\
  Function: display_error
  Purpose:  displays an error based on the particular error message flag 
            specified by the incoming parameter. After outputting the message, 
            exits the program.
\*----------------------------------------------------------------------------*/
function display_error($message_flag)
{

  $message_html = '';
  switch($message_flag)
  {
    case "no_recipient":
      $message_html = "<p>No recipient email address has been specified by the "
         . "form page. This specifies who will receive the information contained "
         . "in the form.</p>"
         . "<p>To solve this problem, take a look at your form and make sure "
         . "it contains the following hidden field:</p>"
         . "<p class='html'>&lt;input type='hidden' name='recipient' "
         . "value='<span class=\"replace_text\">enter_email@address.here</span>' /&gt;</p>"
         . "<p>[And be sure to include the correct recipient email address!]</p>"; 
      break;

    case "invalid_recipient":
      $message_html = "<p>The recipient email address is invalid: "
         . "<b>" . $_POST['recipient'] . "</b>. Please "
         . "check to make sure the following tag in your form page contains a "
         . "valid email address:</p>"
         . "<p class='html'>&lt;input type='hidden' name='recipient' "
         . "value='<span class=\"replace_text\">enter_email@address.here</span>' /&gt;</p>";
      break;

    case "no_redirect_page":
      $message_html = "<p>No redirect page has been specified in the form page. This "
         . "lets you choose where the user will be redirected to after the "
         . "email is sent by this script.</p>"
         . "<p>To solve this problem, add the following hidden field to your "
         . "form page:</p>"
         . "<p class='html'>&lt;input type='hidden' name='redirect_page' "
         . "value='<span class=\"replace_text\">FULL_URL_HERE'</span> /&gt;</p>";
      break;

    case "domain_not_found":
      $message_html = "<p>This referral domain has not been specified in the script. "
         . "Please see the <b>readme.txt</b> file for instructions on how to "
         . "properly configure the script to be allowed to receive form data from "
         . "this URL.</p>";
      break;
      
    case "cannot_send_email":
      $message_html = "<p>The email could not be sent. This usually means that either PHP "
         . "has not been configured to sent emails on this server or the service is "
         . "temporarily down</p>";
      break;

    case "cannot_send_recipient_email":
      $message_html = "<p>The recipient email could not be sent.</p>";
      break;
      
    default: 
      $message = "Unknown error flag.";
  }


  echo <<<OUTPUT

<html>
<head>
  <title>An error has occurred</title>

<style type='text/css'>
p, table, td  { font-family: arial; font-size: 8pt; }
.html         { margin: 15px; font-family: courier; }
#error_title  { font-weight: bold; color: white; }
.replace_text { color: green; }
</style>

</head>
<body>

  <table width='400' height='100' align='center' valign='center' 
    style='border: 1px solid #999999; background-color: #efefef;'>
  <tr>
    <td height='10' bgcolor='#336699' id='error_title'>&nbsp;Error:</td>
  </tr>
  <tr>
    <td>$message_html</td>
  </tr>
  </table>

</body>
</html>

OUTPUT;

  exit();

}

?>
