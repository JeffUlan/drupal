<?

function navigation() { 
 ?>
  <P ALIGN="center">[ <A HREF="account.php">User info</A> | <A HREF="account.php?op=edituser">Edit user info</A> | <A HREF="account.php?op=edithome">Customize homepage</A> | <A HREF="account.php?op=editcomm">Customize comments</A> | <A HREF="account.php?op=logout">Logout</A> ]</P>
 <?
}

function validateAccount($uname, $email) {

  ### Verify username and e-mail address:
  if ((!$email) || ($email=="") || (strrpos($uname,' ') > 0) || (!eregi("^[_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3}$", $email))) $rval = "the specified e-mail address is not valid.<BR>";
  if ((!$uname) || ($uname=="") || (ereg("[^a-zA-Z0-9_-]",$uname))) $rval = "the specified username '$uname' is not valid.<BR>";
  if ((strlen($uname) > 15) || (strrpos($uname,' ') > 0)) $rval = "the specified username is too long: it must be less than 15 characters.";
  if (eregi("^((root)|(httpd)|(operator)|(admin)|(administrator)|(news)|(deamon)|(nobody)|(ftp))$", $uname)) $rval = "the specified username is reserved.";

  ### Verify whether username and e-mail address are uniqua:
  dbconnect();
  if (mysql_num_rows(mysql_query("select uname from users where uname='$uname'")) > 0) $rval = "the specified username is already taken.";
  if (mysql_num_rows(mysql_query("select email from users where email='$email'")) > 0) $rval = "the specified e-mail address is already registered.";
  return($rval);
}

function generatePassword($dictionary = "password.dict", $min_length = 6, $max_length = 9) {
  mt_srand((double)microtime()*1000000);
  $fp=fopen($dictionary, "r");
  $size=filesize($dictionary);

  while(strlen($password) < $min_length) {
    ### Move to a random spot in the file:
    fseek($fp,mt_rand(0,$size-8));
    ### Finish off the current word:
    fgets($fp,4096);	 
    $word=trim(fgets($fp,4096));
    if((strlen($word) + strlen($password)) <= $max_length) $password.=$word;
  }
  fclose($fp);		 
  return $password;	
}

function confirmNewUser($uname, $email) {
  include "functions.inc";
  include "theme.inc";
  $theme->header();

  if ($error = validateAccount($uname, $email)) {
    print "<B>Error:</B> $error";
  }
  else {
    ### Display account information:
    print "<U>Account information:</U><BR><UL><LI>username: $uname</LI><LI>e-mail address: $email</LI></UL>";
    ?>
     <FORM ACTION="account.php" METHOD="post">
      <INPUT TYPE="hidden" NAME="uname" VALUE="<?PHP echo"$uname"; ?>">
      <INPUT TYPE="hidden" NAME="email" VALUE="<?PHP echo"$email"; ?>">
      <BR><BR><INPUT TYPE="submit" NAME="op" VALUE="Create account">
     </FORM>
    <?
  }
  $theme->footer();
}

function finishNewUser($uname, $email) {
  include "functions.inc";
  include "theme.inc";
  $theme->header();

  dbconnect();

  $pass = generatePassword();
  $result = mysql_query("insert into users values (NULL,'','$uname','$email','','','$pass',10,'',0,0,0,'',0,'','','$commentlimit')");

  if (!$result) {
    echo mysql_errno(). ": ".mysql_error(). "<BR>";
  } 
  else {
    if ($system == 1) {
      echo "Your password is: <B>$pass</B><BR>";
      echo "<A HREF=\"account.php?op=login&uname=$uname&pass=$makepass\">Login</A> to change your personal settings.";
    } else {
      $message = "Your $sitename member account has been created succesfully.  To be able to use it you must login using the information below.  Please save this mail for further reference.\n\n   username: $uname\n     e-mail: $email\n   password: $pass\n\nThis password is generated by a randomizer.  It is recommended that you change this password immediately.\n\n$contact_signature";
      $subject="Account details for $sitename";
      mail($email, $subject, $message, "From: $contact_email\nX-Mailer: PHP/" . phpversion());
      echo "Your member account has been created and the details necessary to login have been sent to your e-mail account <B>$email</B>.  Once you received the account confirmation, hit <A HREF=\"account.php\">this link</A> to login.";
    }
  }
  $theme->footer();
}


function userinfo($uname) {
  global $user, $cookie;

  $result = mysql_query("SELECT femail, url, bio, signature FROM users WHERE uname = '$uname'");
  $userinfo = mysql_fetch_array($result);

  
  cookiedecode($user);
  
  include "theme.inc";
  $theme->header();

  if ($uname == $cookie[1]) {
    print "<P>Welcome $uname!  This is <B>your</B> user info page.  There are many more, but this one is yours. You are probably most interested in editing something, but if you need to kill some time, this place is as good as any other place.</P>";
  }
  if ((mysql_num_rows($result) == 1) && ($userinfo[url] || $userinfo[femail] || $userinfo[bio])) {
    print "<TABLE WIDTH=\"100%\">";
    ### Name:
    print "<TR><TD ALIGN=\"right\"><B>Name:</B></TD><TD><B>$uname</B></TD></TR>\n";
    ### URL:
    if ($userinfo[url]) print "<TR><TD ALIGN=\"right\"><B>URL:</B></TD><TD><A HREF=\"$userinfo[url]\">$userinfo[url]</A></TD></TR>\n";
    else print "<TR><TD ALIGN=\"right\"><B>URL:</B></TD><TD>not available</TD></TR>\n";    
    ### E-mail:
    if ($userinfo[femail]) print "<TR><TD ALIGN=\"right\" VALIGN=\"top\"><B>E-mail:</B></TD><TD><A HREF=\"mailto:$userinfo[femail]\">$userinfo[femail]</A><BR><I>(Might be spam-proofed or even completly fake.)</I></TD></TR>\n";
    else print "<TR><TD ALIGN=\"right\"><B>E-mail:</B></TD><TD>not available</TD></TR>\n";    
    ### Bio:
    if ($userinfo[bio]) print "<TR><TD ALIGN=\"right\" VALIGN=\"top\"><B>Bio:</B></TD><TD>". nl2br($userinfo[bio]) ."</TD></TR>\n";
    else print "<TR><TD ALIGN=\"right\"><B>Bio:</B></TD><TD>not available</TD></TR>\n";        
    ### Signature:
    if ($userinfo[bio]) print "<TR><TD ALIGN=\"right\" VALIGN=\"top\"><B>Signature:</B></TD><TD>". nl2br($userinfo[signature]) ."</TD></TR>\n";
    else print "<TR><TD ALIGN=\"right\"><B>Signature:</B></TD><TD>not available</TD></TR>\n";        
    print "</TABLE><BR><BR>";
  } else {
    echo "<P>No information available for <B>$uname</B>.</P>";
  }
  $theme->footer();
}

function main($user) {
  global $fail;
  if(!isset($user)) {
    include "config.inc";
    include "functions.inc";
    include "theme.inc";
    $theme->header();
  ?>
  <?
    if ($fail) print "<CENTER><BLINK><H3>Authentication failed!</H3></BLINK></CENTER>"; 
  ?>
  <TABLE BORDER="0" CELLPADDING="2" CELLSPACING="0" WIDTH="100%">
   <TR>
    <TD ALIGN="center" VALIGN="bottom" WIDTH="33%">
     <FORM ACTION="account.php" METHOD="post">
      <?
       $theme->box("Login", "<TABLE BORDER=\"0\"><TR><TD ALIGN=\"right\" WIDTH=\"80\">Username:</TD><TD><INPUT TYPE=\"text\" NAME=\"uname\" SIZE=\"12\" MAXLENGHT=\"15\"></TD></TR><TR><TD ALIGN=\"right\">Password:</TD><TD><INPUT TYPE=\"password\" NAME=\"pass\" SIZE=\"12\" MAXLENGTH=\"12\"></TD></TR><TR><TD ALIGN=\"center\" COLSPAN=\"2\"><INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Login\"></TD></TR></TABLE>");
      ?>
     </FORM>
    </TD>
    <TD ALIGN="center" VALIGN="bottom" WIDTH="33%">
     <FORM ACTION="account.php" METHOD="post">
      <?
       $theme->box("Forgot your password?", "<TABLE BORDER=\"0\"><TR><TD ALIGN=\"right\" WIDTH=\"80\">Username:</TD><TD><INPUT TYPE=\"text\" NAME=\"uname\" SIZE=\"12\" MAXLENGHT=\"15\"></TD></TR><TR><TD COLSPAN=\"3\"><FONT SIZE=\"2\"><I>Fill out your username and your password will be mailed to the e-mail account associated with your username.</I></FONT></TD></TR><TR><TD ALIGN=\"center\" COLSPAN=\"2\"><INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Mail password\"></TD></TR></TABLE>");
      ?>
     </FORM>
    </TD>
    <TD ALIGN="center" VALIGN="bottom" WIDTH="33%">
     <FORM ACTION="account.php" METHOD="post">
      <?
       $theme->box("Register as new user", "<TABLE BORDER=\"0\"><TR><TD ALIGN=\"right\" WIDTH=\"80\">Username:</TD><TD><INPUT TYPE=\"text\" NAME=\"uname\" SIZE=\"12\" MAXLENGTH=\"20\"></TD></TR><TR><TD ALIGN=\"right\">E-mail:</TD><TD><INPUT TYPE=\"text\" NAME=\"email\" SIZE=\"12\" MAXLENGTH=\"55\"></TD></TR><TR><TD ALIGN=\"center\" COLSPAN=\"2\"><INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Sign up\"></TD></TR></TABLE>");
      ?>
     </FORM>
    </TD>
   </TR>
   <TR>
    <TD COLSPAN="3">
     <P>Logging in will allow you to post comments as yourself. If you don't login, you will only be able to post as <B><?php echo"$anonymous"; ?></B>.</P>
    </TD>
   </TR>
  </TABLE>
 <?PHP
  $theme->footer();
  } 
  elseif(isset($user)) {
    global $cookie;
    include "functions.inc";
    cookiedecode($user);
    dbconnect();
    userinfo($cookie[1]);
  }
}

function logout() {
  setcookie("user");
  include "functions.inc";
  include "theme.inc";
  $theme->header();
 ?>
  <BR><BR><BR><BR>
  <P ALIGN="center"><FONT SIZE="+2"><B>You are now logged out!</B></FONT></P>
  <P>You have been logged out of the system.  Since authentication details are stored by using cookies, logging out is only necessary to prevent those who have access to your computer from abusing your account.</P>
 <?
  $theme->footer();
}

function mailPassword($uname) {
  include "functions.inc";
  dbconnect();
  $result = mysql_query("select pass, email from users where uname = '$uname'");
  if(!$account = mysql_fetch_object($result)) {
    echo "Sorry, no corresponding account information was found.";
  } else {
    $message = "$uname,\n\n\na visitor from ".getenv("REMOTE_ADDR")." (most probably you) has just requested the password associated with the e-mail address '$account->email', to be sent.  The password is '$account->pass' (without the quotes).\n\nIf you didn't ask for this, don't get your panties all in a knot.  You are seeing this message, not 'them'.  So if you can't be trusted with your own password, we might have an issue, otherwise, you can just disregard this message.\n\n\n$contact_signature";
    $subject="[$sitename] password for $account->uname";
    mail($account->email, $subject, $message, "From: $contact_email\nX-Mailer: PHP/" . phpversion());
    $titlebar = "You password has been sent.";
    include "theme.inc";
    $theme->header();
    print "The requested password has been sent to the e-mail account associated with the username '<B>$uname</B>'.";
    $theme->footer();
  }
}

function docookie($setuid, $setuname, $setpass, $setstorynum, $setumode, $setuorder, $setthold, $setnoscore, $setublockon, $settheme) {
  $info = base64_encode("$setuid:$setuname:$setpass:$setstorynum:$setumode:$setuorder:$setthold:$setnoscore:$setublockon:$settheme");
  setcookie("user","$info", time() + 15552000); // 6 month = 15552000
}

function login($uname, $pass) {
  global $setinfo;
  include "functions.inc";
  dbconnect();
  $result = mysql_query("select uid, storynum, umode, uorder, thold, noscore, ublockon, theme, signature FROM users WHERE uname = '$uname' AND pass = '$pass'");
  if (mysql_num_rows($result) == 1) {
    $setinfo = mysql_fetch_array($result);
    docookie($setinfo[uid], $uname, $pass, $setinfo[storynum], $setinfo[umode], $setinfo[uorder], $setinfo[thold], $setinfo[noscore], $setinfo[ublockon], $setinfo[theme]);
    Header("Location: account.php?op=userinfo&uname=$uname");
  } else {
    Header("Location: account.php?fail=1");
  }
}

function user_edit_info() {
  include "functions.inc";
  global $user, $userinfo;
  getusrinfo($user);

  include "theme.inc";
  $theme->header();
  ?>
  
  <FORM ACTION="account.php" METHOD="post">
        
   <B>Real name:</B><BR>
   <INPUT TYPE="text" name="name" value="<?PHP echo"$userinfo[name]"; ?>" SIZE="30" MAXLENGHT="55"><BR>
   <I>Optional.</I><BR><BR>

   <B>Real e-mail address:</B><BR>
   <INPUT TYPE="text" NAME="email" VALUE="<?PHP echo"$userinfo[email]"; ?>" SIZE="30" MAXLENGHT="55"><BR>
   <I>Required, but never displayed publicly: needed in case you lose your password.</I><BR><BR>

   <B>Fake e-mail address:</B><BR>
   <INPUT TYPE="text" NAME="femail" VALUE="<?PHP echo"$userinfo[femail]"; ?>" SIZE="30" MAXLENGHT="55"><BR>
   <I>Optional, and displayed publicly by your comments.  You may spam proof it if you want.</I><BR><BR>

   <B>URL of homepage:</B><BR>
   <INPUT TYPE="text" name="url" value="<?PHP echo"$userinfo[url]"; ?>" SIZE="30" MAXLENGTH="100"><BR>
   <I>Optional, but make sure you enter fully qualified URLs only.  That is, remember to include "http://".</I><BR><BR>

   <B>Bio:</B> (255 char limit)<BR>
   <TEXTAREA WRAP="virtual" COLS="50" ROWS="5" NAME="bio"><?PHP echo"$userinfo[bio]"; ?></TEXTAREA><BR>
   <I>Optional.  This biographical information is publicly displayed on your user page.</I><BR><BR>

   <B>Password:</B> <BR>
   <INPUT TYPE="password" NAME="pass" SIZE="10" MAXLENGTH="20"> <INPUT TYPE="password" NAME="vpass" SIZE="10" MAXLENGTH="20"><BR>
   <I>Enter your new password twice if you want to change your current password or leave it blank if you are happy with your current password.</I><BR><BR>
	
   <INPUT TYPE="hidden" NAME="uname" VALUE="<?PHP echo"$userinfo[uname]"; ?>">
   <INPUT TYPE="hidden" NAME="uid" VALUE="<?PHP echo"$userinfo[uid]"; ?>">
   <INPUT TYPE="submit" NAME="op" VALUE="Save user information">
 
  </FORM>
  
  <?PHP
   $theme->footer();
}

function user_save_info($uid, $name, $uname, $email, $femail, $url, $pass, $vpass, $bio) {
  global $user, $cookie, $userinfo;
  include "functions.inc";
  if ((isset($pass)) && ("$pass" != "$vpass")) {
    echo "The verification password is not the same as the first password.";
  } 
  elseif (($pass != "") && (strlen($pass) < $minpass)) {
    echo "Sorry, your password must be at least $minpass charachters long.";
  } 
  else {
    if ($bio) { 
      $bio = FixQuotes($bio); 
    }
    if ($pass != "") {
      dbconnect();
      cookiedecode($user);
      mysql_query("UPDATE users SET name = '$name', email = '$email', femail = '$femail', url = '$url', pass = '$pass', bio = '$bio' WHERE uid = $uid");
      $result = mysql_query("SELECT uid, uname, pass, storynum, umode, uorder, thold, noscore, ublockon, theme from users where uname='$uname' and pass='$pass'");
      $userinfo = mysql_fetch_array($result);
      docookie($userinfo[uid],$userinfo[uname],$userinfo[pass],$userinfo[storynum],$userinfo[umode],$userinfo[uorder],$userinfo[thold],$userinfo[noscore],$userinfo[ublockon],$userinfo[theme]); 
    } 
    else {
      dbconnect();
      mysql_query("UPDATE users SET name = '$name', email = '$email', femail = '$femail', url = '$url', bio = '$bio' WHERE uid=$uid");
    }
  }
}

function user_edit_home() {
  include "functions.inc";
  global $user, $userinfo;
  getusrinfo($user);
  include "theme.inc";
  $theme->header();
	
  ?>
  <FORM ACTION="account.php" method="post">

  <P>	
   <B>Maximum number of stories:</B><BR>
   <INPUT TYPE="text" NAME="storynum" SIZE="3" MAXLENGHT="3" VALUE="<?PHP echo"$userinfo[storynum]"; ?>">
  </P>

  <P>
   <B>Theme:</B><BR>
   <SELECT NAME="theme">
   <?php
     include "themes/list.php";
     $themelist = explode(" ", $themelist);
     for ($i=0; $i < sizeof($themelist); $i++) {
       if ($themelist[$i]!="") {
         echo "<OPTION VALUE=\"$themelist[$i]\" ";
         if ((($userinfo[theme]=="") && ($themelist[$i]=="default")) || ($userinfo[theme]==$themelist[$i])) echo "SELECTED";
	 echo ">$themelist[$i]\n";
       }
     }
     if ($userinfo[theme]=="") $userinfo[theme] = "default";
   ?>
   </SELECT><BR>
   <I>Changes the look and feel of the site.</I>
  </P>

  <P>
   <B>User block:</B><BR>
   <TEXTAREA WRAP="virtual" COLS="50" ROWS="5" NAME="ublock"><? echo"$userinfo[ublock]"; ?></TEXTAREA><BR>
   <INPUT TYPE="checkbox" NAME="ublockon" <? if ($userinfo[ublockon]==1) { echo "checked"; } ?>> Enable user box.<BR>
   <I>Enable the checkbox and whatever you enter below will appear on your costum main page.</I>
  </P>

  <INPUT TYPE="hidden" name="uname" value="<?PHP echo"$userinfo[uname]"; ?>">
  <INPUT TYPE="hidden" name="uid" value="<?PHP echo"$userinfo[uid]"; ?>">
  <INPUT TYPE="submit" name="op" value="Save homepage settings">
  </FORM>
  <?PHP
  $theme->footer();
}

function user_save_home($uid, $uname, $storynum, $theme, $ublockon, $ublock) {
	global $user, $userinfo;
	include "functions.inc";
	dbconnect();
	if(isset($ublockon)) $ublockon=1; else $ublockon=0;	
	$ublock = FixQuotes($ublock);
	mysql_query("LOCK TABLES users WRITE");
	mysql_query("update users set storynum='$storynum', ublockon='$ublockon', ublock='$ublock', theme='$theme' where uid=$uid");
	getusrinfo($user);
	mysql_query("UNLOCK TABLES");
	docookie($userinfo[uid],$userinfo[uname],$userinfo[pass],$userinfo[storynum],$userinfo[umode],$userinfo[uorder],$userinfo[thold],$userinfo[noscore],$userinfo[ublockon],$userinfo[theme]);
	Header("Location: account.php?theme=$theme");
}

function user_edit_comm() {
  include "functions.inc";
  global $user, $userinfo;
  getusrinfo($user);

  include "theme.inc";
  $theme->header();
  ?>
	
  <FORM ACTION="account.php" METHOD="post">
   <B>Display Mode:</B><BR>
   <SELECT NAME="umode">
    <OPTION VALUE="nocomments" <?PHP if ($userinfo[umode] == 'nocomments') { echo "SELECTED"; } ?>>No comments
    <OPTION VALUE="nested" <?PHP if ($userinfo[umode] == 'nested') { echo "SELECTED"; } ?>>Nested
    <OPTION VALUE="flat" <?PHP if ($userinfo[umode] == 'flat') { echo "SELECTED"; } ?>>Flat
    <OPTION VALUE="threaded" <?PHP if (!isset($userinfo[umode]) || ($userinfo[umode]=="") || $userinfo[umode]=='threaded') { echo "SELECTED"; } ?>>Threaded
   </SELECT>
   <BR><BR>

   <B>Sort order:</B><BR>
   <SELECT NAME="uorder">
    <OPTION VALUE="0" <?PHP if (!$userinfo[uorder]) { echo "SELECTED"; } ?>>Oldest first
    <OPTION VALUE="1" <?PHP if ($userinfo[uorder]==1) { echo "SELECTED"; } ?>>Newest first
    <OPTION VALUE="2" <?PHP if ($userinfo[uorder]==2) { echo "SELECTED"; } ?>>Highest scoring first
   </SELECT>
   <BR><BR>

   <B>Threshold:</B><BR>
   <SELECT NAME="thold">
    <OPTION VALUE="-1" <?PHP if ($userinfo[thold]==-1) { echo "SELECTED"; } ?>>-1: Display uncut and raw comments.
    <OPTION VALUE="0" <?PHP if ($userinfo[thold]==0) { echo "SELECTED"; } ?>>0: Display almost all comments.
    <OPTION VALUE="1" <?PHP if ($userinfo[thold]==1) { echo "SELECTED"; } ?>>1: Display almost no anonymous comments.
    <OPTION VALUE="2" <?PHP if ($userinfo[thold]==2) { echo "SELECTED"; } ?>>2: Display comments with score +2 only.
    <OPTION VALUE="3" <?PHP if ($userinfo[thold]==3) { echo "SELECTED"; } ?>>3: Display comments with score +3 only.
    <OPTION VALUE="4" <?PHP if ($userinfo[thold]==4) { echo "SELECTED"; } ?>>4: Display comments with score +4 only.
    <OPTION VALUE="5" <?PHP if ($userinfo[thold]==5) { echo "SELECTED"; } ?>>5: Display comments with score +5 only.
   </SELECT><BR>
   <I>Comments that scored less than this setting will be ignored.<BR>Anonymous comments start at 0, comments of people logged on start at 1 and moderators can add and subtract points.</I>
   <BR><BR>

   <B>Signature:</B> (255 char limit)<BR>
   <TEXTAREA WRAP="virtual" COLS="50" ROWS="4" NAME="signature"><?PHP echo "$userinfo[signature]"; ?></TEXTAREA><BR>
   <I>Optional.  This information will be publicly displayed at the end of your comments.</I>
   <BR><BR>

   <INPUT TYPE="hidden" NAME="uname" VALUE="<?PHP echo"$userinfo[uname]"; ?>">
   <INPUT TYPE="hidden" NAME="uid" VALUE="<?PHP echo"$userinfo[uid]"; ?>">
   <INPUT TYPE="submit" NAME="op" VALUE="Save comments settings">
  </FORM>
  <?PHP
  $theme->footer();
}

function user_save_comm($uid, $uname, $umode, $uorder, $thold, $noscore, $signature) {
  global $user, $userinfo;
  include "functions.inc";
  dbconnect();
  if(isset($noscore)) $noscore = 1; else $noscore = 0;
  mysql_query("LOCK TABLES users WRITE");
//  print "UPDATE users SET umode = '$umode', uorder = '$uorder', thold = '$thold', noscore = '$noscore', signature = '$signature' WHERE uid = $uid<BR>";
  mysql_query("UPDATE users SET umode = '$umode', uorder = '$uorder', thold = '$thold', noscore = '$noscore', signature = '$signature' WHERE uid = $uid");
  getusrinfo($user);
  mysql_query("UNLOCK TABLES");
  docookie($userinfo[uid],$userinfo[uname],$userinfo[pass],$userinfo[storynum],$userinfo[umode],$userinfo[uorder],$userinfo[thold],$userinfo[noscore],$userinfo[ublockon],$userinfo[theme]);
  Header("Location: account.php");
}

switch($op) {
  case "logout":
    logout();
    break;
  case "lost_pass":
    lost_pass();
    break;
  case "Sign up":
    confirmNewUser($uname, $email);
    break;
  case "Create account":
    finishNewUser($uname, $email);
    break;
  case "Mail password":
    mailPassword($uname);
    break;
  case "userinfo":
    include "functions.inc";
    dbconnect();
    userinfo($uname);
    break;
  case "Login":
    login($uname, $pass);
    break;
  case "dummy":
    // this is needed to give the cookie a chance to digest
    include "config.inc";
    header("Location: account.php");
    break;
  case "edituser":
    user_edit_info();
    break;
  case "Save user information":
    user_save_info($uid, $name, $uname, $email, $femail, $url, $pass, $vpass, $bio);
    userinfo($uname);
    break;
  case "edithome":
    user_edit_home();
    break;
  case "Save homepage settings":
    user_save_home($uid, $uname, $storynum, $theme, $ublockon, $ublock);
    userinfo($uname);
    break;
  case "editcomm":
    user_edit_comm();
    break;
  case "Save comments settings":
    user_save_comm($uid, $uname, $umode, $uorder, $thold, $noscore, $signature);
    userinfo($uname);
    break;
  default:
    main($user);
    break;
}
?>