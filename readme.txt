
readme.txt for email.php
------------------------

This document assumes you have at least a partial familiarity with HTML and
know how to build a basic webform. As such, this readme.txt file is really
only about how to INTEGRATE your existing web form with email.php so that you
can use the script to send your emails.


Changelog
---------

  1.0         Aug 01, 2005     Original version
  1.1         Oct 22, 2006     - custom templates to allow for complete
                                 control over email formatting
                               - control over checking sender source or not
                               - option to send "receipt" email to sender
                               - 4.3 now required (used to be 4.1)
                               - option to add placeholders in email and email
                                 recipient subject headings
  1.2         Aug 29, 2007     - placeholders may now be used in the email_from
                                 field

How to use this script
----------------------

Configuring your webform:

1. First make sure your webpage form tag is sending its information to the
   emailer script, like so:

     <form method='post' action="email.php" />

   (if this file is not located in the same directory, make sure you either
   include the full URL or relative URL to the file)

   Next, include the following two fields (in between the <form> and </form>)
   tags:

     <input type='hidden' name='recipient' value='RECIPIENT_EMAIL_ADDRESS_HERE' />
     <input type='hidden' name='redirect_page' value='FULL_REDIRECT_URL_HERE' />

   - The recipient specifies WHO will receive the email
   - the redirect_page specifies WHERE the user will be redirected to after
   having submitted the form.
   - make sure you include the FULL redirect URL, including "http://", otherwise
   it may not redirect properly.


2. Open up the email.php script file, and find the section entitled
   "*** USER SETTINGS ***". In it, you'll see a line that looks like this:

     $valid_sites = array("www.yourwebsite.com", "localhost");

   This setting lets you specify WHERE the script may receive form information
   from. [This prevents anyone over the internet using this script to send their
   emails!]

   What you'll need to do is update the "www.yourwebsite.com" value for whatever
   domain name your form page is located. e.g. if your file is located here:

     www.thisismydomain.org/dir1/form.html

   you would add "www.thisismydomain.org". You may add as many domains as you
   want, provided each one is within double-quotes, and delimited by a comma.
   e.g.

     $valid_sites = array("www.site1.com", "www.site2.com", "www.site3.com");

   Including "http://" is not necessary (nor is the www. part either, actually -
   but they're all valid).




Special form fields
-------------------

Here is a list of all "special" form fields, and what they mean.

REQUIRED:

  recipient         -the email recipient
  redirect_page     -where the user is redirected to after form submission

OPTIONAL:

  "email_subject"   -allows you to specify the email subject line
  "cc"              -carbon copy: sends an email to this person IN ADDITION to the
                     recipient. For multiple cc's, use cc1, cc2, cc3 ...
                     but be sure to include the initial "cc" field first.
  "bcc"             -blind carbon copy: these send emails to additional people,
                     just like the "cc" fields, but they're names won't appear in
                     the "Sent to" field. Like "cc", you can include multiple
                     bcc fields with "bcc1", "bcc2", etc.
  "email_from"      -specifies WHO sent the email
  "reply_to"        -determines the "reply to" email address for each email sent
  "email_template"  -this optional field allows you to define the location of a
                     text file containing the content of the email to be sent. This
                     file may contain placeholders of the form %fieldname% which,
                     when sent, are replaced with the content of that form field.
                     For an example of how to use email templates, take a look at
                     example2.html and the corresponding email_template.txt file in
                     the downloadable zipfile. Note that:
                       * Placeholders are case sensitive and should be identical to
                         the name attribute of the form field.
                       * If your name attribute contains spaces, the placeholder
                         should have underscores instead, for example:

                           <input type="text" name="My text field" value="" />

                         would have a placeholder of:

                           %My_text_field%

  "receipt_email_field"    -if you wish to send a receipt email to the person
                            submitting the form, you must include this field. This
                            field should have the name of the form field which stores
                            the person's email address. If it is not defined, they
                            will not receive an email.
  "receipt_email_subject"  -The subject heading of the receipt email. May contain
                            placeholders.
  "receipt_email_template" -This field is required for receipt emails. Like the
                            email_template above, this field should contain the
                            location of a text file containing the content of the
                            receipt email to be sent. As before, this field may
                            contain placeholders.


Example use of optional fields:
  <input type='hidden' name='cc' value='extra@person1.com' />
  <input type='hidden' name='cc1' value='extra@person2.com' />

All other form fields will have their information submitted along with the email.

If the information being sent to it isn't in the correct form, it will issue
an error message explaining how to fix the problem. Hopefully they're quite
clear, so you won't have any trouble getting it up and running quickly and
easily!



Other Notes
-----------

*** Any underscores in the name attributes of the input fields will be converted
to spaces in the emails sent. ***
