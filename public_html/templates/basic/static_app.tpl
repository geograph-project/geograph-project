{assign var="page_title" value="Geograph App"}
{include file="_std_begin.tpl"}

<section id="geograph-app-landing" style="max-width:60em">
    <h2>The Geograph App</h2>
    
    <p class="intro">Our app allows you to take, upload, and submit photos to <b>Geograph Britain and Ireland</b>, review your recent submissions, view maps, and search for images 
        directly from your device.</p>

    {dynamic}
    {if $user->registered}
        <div class="user-status interestBox" style=" border-radius:9px">
            As you are already registered, you can simply log in with your existing credentials when you first open the app.
        </div><br>
    {else}
        <div id="post-reg-notice" style="display:none; background: #fffde7; border: 1px solid #ffd54f; padding: 1em; margin: 1em 0;  border-radius:9px">
            <h3 style="margin-top:0">Registration received!</h3>
            <p>Please check your email and follow the confirmation link to activate your account. Once confirmed, you will be able to use your new login details in the app.</p>
        </div>

        <div id="registration-notice" style="border: 1px solid #ccc; padding: 1em; margin: 1em 0; background-color:#e4e4fc;  border-radius:9px">
            <h3 style="margin-top:0">Before you start</h3>
            <p>To use the app, you must have a Geograph account. Registration is not yet available inside the app, so please create your account here first.</p>
            &#128073; <a href="/register.php?redir=/help/app%3Fregistered"><strong>Register for Geograph</strong></a>
        </div>
    {/if}
    {/dynamic}

    <div id="app-instructions">
        <h2 id="platform-heading">Get the App</h2>
        
        <div id="desktop-msg" style="display:none;">

<div style="float:right; width:150px; height: 150px">
<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" xmlns:xlink="http://www.w3.org/1999/xlink" style="max-width: 100%;height: auto;" width="999" height="999" viewBox="0 0 999 999">
<rect width="999" height="999" fill="#ffffff" x="0" y="0"></rect>
<g fill="#000000">
<g transform="translate(270,54) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(324,54) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(405,54) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(486,54) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(513,54) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(540,54) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(567,54) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(594,54) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(675,54) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,54) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(270,81) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(297,81) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(378,81) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(405,81) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(486,81) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(513,81) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(567,81) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,81) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,81) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(270,108) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(297,108) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(378,108) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(486,108) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(540,108) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(621,108) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,108) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(675,108) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,108) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(378,135) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(405,135) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(432,135) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(459,135) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(486,135) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(540,135) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(567,135) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,135) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(675,135) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,135) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(297,162) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(324,162) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(378,162) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(405,162) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(432,162) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(594,162) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(621,162) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,162) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(270,189) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(297,189) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(324,189) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(351,189) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(378,189) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(432,189) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(459,189) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(513,189) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(594,189) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,189) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(270,216) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(324,216) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(378,216) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(432,216) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(486,216) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(540,216) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(594,216) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,216) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,216) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(270,243) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(324,243) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(351,243) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(459,243) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(486,243) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(540,243) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(594,243) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(621,243) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,243) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(675,243) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(108,270) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(135,270) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(162,270) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(216,270) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(270,270) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(324,270) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(378,270) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(486,270) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(567,270) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(594,270) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(675,270) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,270) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(729,270) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(756,270) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(783,270) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(864,270) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(891,270) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(918,270) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(243,297) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(270,297) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(297,297) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(324,297) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(432,297) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(459,297) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(567,297) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(594,297) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(675,297) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(729,297) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(756,297) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(864,297) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(891,297) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(918,297) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(81,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(135,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(162,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(216,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(243,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(270,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(324,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(351,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(378,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(405,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(459,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(486,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(513,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(729,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(756,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(783,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(837,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(864,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(891,324) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(81,351) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(108,351) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(135,351) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(189,351) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(459,351) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(486,351) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(513,351) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(594,351) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(675,351) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,351) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(756,351) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(837,351) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(864,351) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(891,351) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(918,351) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(81,378) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(135,378) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(162,378) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(216,378) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(351,378) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(378,378) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(486,378) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(513,378) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(540,378) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(567,378) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(594,378) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(621,378) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,378) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,378) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(729,378) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(810,378) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(837,378) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(891,378) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(135,405) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(162,405) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(189,405) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(270,405) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(351,405) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(378,405) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(513,405) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(675,405) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(756,405) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(918,405) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(81,432) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(108,432) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(135,432) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(162,432) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(189,432) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(216,432) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(270,432) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(432,432) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(594,432) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,432) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(729,432) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(756,432) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(837,432) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(891,432) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(81,459) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(135,459) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(324,459) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(351,459) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(378,459) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(405,459) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(432,459) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(540,459) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(567,459) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(594,459) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(621,459) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,459) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(756,459) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(783,459) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(864,459) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(54,486) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(81,486) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(108,486) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(216,486) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(243,486) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(270,486) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(351,486) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(432,486) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(459,486) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(567,486) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(594,486) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(729,486) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(783,486) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(810,486) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(54,513) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(108,513) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(135,513) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(162,513) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(270,513) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(324,513) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(351,513) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(405,513) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(459,513) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(621,513) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(675,513) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,513) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(756,513) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(783,513) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(918,513) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(81,540) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(135,540) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(189,540) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(216,540) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(243,540) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(378,540) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(432,540) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(486,540) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(540,540) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,540) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(783,540) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(864,540) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(891,540) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(81,567) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(108,567) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(135,567) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(162,567) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(378,567) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(405,567) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(513,567) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(540,567) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(567,567) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(621,567) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,567) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(675,567) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,567) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(783,567) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(810,567) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(837,567) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(864,567) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(891,567) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(54,594) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(81,594) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(108,594) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(135,594) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(162,594) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(216,594) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(243,594) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(270,594) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(297,594) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(513,594) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(594,594) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,594) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,594) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(729,594) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(783,594) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(810,594) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(837,594) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(891,594) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(54,621) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(108,621) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(162,621) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(189,621) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(297,621) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(324,621) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(432,621) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(513,621) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,621) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(675,621) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,621) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(729,621) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(756,621) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(783,621) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(837,621) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(864,621) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(918,621) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(54,648) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(108,648) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(135,648) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(162,648) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(189,648) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(216,648) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(243,648) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(297,648) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(378,648) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(432,648) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(459,648) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,648) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,648) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(810,648) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(837,648) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(891,648) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(54,675) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(108,675) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(135,675) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(189,675) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(297,675) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(459,675) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(486,675) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(540,675) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(594,675) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(621,675) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,675) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(837,675) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(864,675) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(918,675) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(54,702) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(135,702) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(189,702) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(216,702) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(324,702) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(351,702) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(405,702) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(486,702) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(513,702) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(540,702) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(567,702) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,702) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,702) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(729,702) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(756,702) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(783,702) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(810,702) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(837,702) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(270,729) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(297,729) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(324,729) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(351,729) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(378,729) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(405,729) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(432,729) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(459,729) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(486,729) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(513,729) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(540,729) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(594,729) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(621,729) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,729) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(675,729) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,729) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(810,729) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(864,729) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(891,729) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(918,729) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(297,756) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(324,756) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(513,756) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(567,756) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(594,756) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(621,756) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(675,756) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,756) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(756,756) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(810,756) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(864,756) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(891,756) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(486,783) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(540,783) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(567,783) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(621,783) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,783) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(675,783) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,783) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(810,783) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(837,783) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(864,783) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(891,783) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(918,783) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(270,810) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(351,810) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(405,810) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(459,810) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(486,810) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(513,810) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(621,810) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,810) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(729,810) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(756,810) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(783,810) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(810,810) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(891,810) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(918,810) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(270,837) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(324,837) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(378,837) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(459,837) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(513,837) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(567,837) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(621,837) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,837) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(675,837) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(729,837) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(810,837) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(837,837) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(918,837) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(270,864) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(297,864) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(324,864) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(351,864) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(378,864) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(405,864) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(432,864) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(459,864) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(486,864) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(594,864) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(621,864) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,864) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(675,864) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(729,864) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(756,864) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(837,864) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(378,891) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(459,891) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(486,891) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(513,891) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(567,891) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(621,891) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(648,891) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(729,891) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(783,891) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(837,891) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(864,891) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(297,918) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(324,918) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(351,918) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(405,918) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(459,918) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(486,918) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(513,918) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(621,918) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(675,918) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(702,918) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(729,918) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(756,918) scale(4.635)"><rect width="6" height="6"></rect></g>
<g transform="translate(891,918) scale(4.635)"><rect width="6" height="6"></rect></g>
</g><g><g transform="translate(54,54)" fill="#000000"><g transform="scale(13.5)"><path d="M0,0v14h14V0H0z M12,12H2V2h10V12z"></path></g></g>
<g transform="translate(756,54)" fill="#000000"><g transform="scale(13.5)"><path d="M0,0v14h14V0H0z M12,12H2V2h10V12z"></path></g></g>
<g transform="translate(54,756)" fill="#000000"><g transform="scale(13.5)"><path d="M0,0v14h14V0H0z M12,12H2V2h10V12z"></path></g></g>
<g transform="translate(108,108)" fill="#000000"><g transform="scale(13.5)"><rect width="6" height="6"></rect></g></g>
<g transform="translate(810,108)" fill="#000000"><g transform="scale(13.5)"><rect width="6" height="6"></rect></g></g>
<g transform="translate(108,810)" fill="#000000"><g transform="scale(13.5)"><rect width="6" height="6"></rect></g></g></g>
</svg>
</div>

            <p>The Geograph App is not found in the Google Play or Apple App Stores. Instead, you access it directly via your web browser, but can also install it to function like a 
                native app, with a quick access icon you can add to your home screen.</p>

            <p>For the best experience (taking and uploading photos in the field), <strong>open this URL on your mobile device:</strong></p>
            
            <p style="font-family: monospace; font-size:1.2em; background: #eee; padding: 10px; display: inline-block;">
                geograph.org.uk/app
            </p>

            <p>Once opened on your mobile, you have two choices:</p>
            <ol>
                <li><strong>Use directly:</strong> Just use it in your mobile browser like any other website.</li>

                <li><strong>Install it:</strong> Use <strong>Chrome</strong> (Android) or <strong>Safari</strong> (iOS) to "Add to Home Screen." This installs the app icon on your 
                device so it works just like a native app.</li>
            </ol>

            <p>Want to see how it looks now?<br>
            &#128073; <a href="https://www.geograph.org.uk/app/" class="button">Launch App on Desktop</a></p>    
        </div>
        
        <div id="mobile-msg" style="display:none;">
            <div style="margin-bottom: 20px;">
                1. <a href="https://www.geograph.org.uk/app/" class="button"
                style="font-weight:bold; font-size: 1.2em; padding: 10px 20px; background: #007aff; color: #fff; text-decoration: none; border-radius: 8px;"
                >Open the App</a>
            </div>

            <div id="install-guide">
                <h3 style="margin-top:0;">2. (Optional) Install to Home Screen</h3>
                
                <div id="android-path" style="display:none;">
                    <div id="is-chrome" style="display:none;">
                        <p>Once the app is open in <strong>Chrome</strong>, tap the menu (&#8942;) and select <strong>"Add to home screen"</strong> or <strong>"Install"</strong> to use it like a native app.</p>
                    </div>
                    <div id="not-chrome" style="display:none;">
                        <p>To install this as a native-style app, please ensure you have opened <strong>geograph.org.uk/app</strong> in the <strong>Google Chrome</strong> browser.</p>
                        <p><The App will use Chrome to run, but you can continue using your favorite browser for everything else.</p>
                    </div>
                </div>

                <div id="ios-path" style="display:none;">
                    <div id="is-safari" style="display:none;">
                        <p>Once the app is open in <strong>Safari</strong>, tap the <strong>Share</strong> icon (square with arrow) and choose <strong>"Add to home screen"</strong>.</p>
                    </div>
                    <div id="not-safari" style="display:none;">
                        <p>To install this as a native-style app, please ensure you have opened <strong>geograph.org.uk/app</strong> in the <strong>Safari</strong> browser.</p>
                        <p>The App will use Safari to run, but you can continue using your favorite browser for everything else.</p>
                    </div>
                </div>
            </div>

            <p>You can always use the app directly without installing.</p>
        </div>
    </div>

    <div style="background-color:#e4e4fc;padding:10px; border-radius:9px">
	We hope you enjoy using the Geograph app. It cost &pound;25k to run Geograph in 2025. Please consider making a one-off or regular donation. See <a href="https://www.geograph.org.uk/help/donate">geograph.org.uk/help/donate</a>
    </div>

    <div class="app-faq-preview">
        <h3>Privacy &amp; Permissions</h3>

        <p><strong>Does the app track my location?</strong> No. As a web app, we only access your location when using the app, and only for explicit features, such as centering the 
        map or geotagging a photo you take. We do not use your position data outside of these functions.</p>

        <h3>Important: Browser Settings</h3>
        <p>The app shares settings with the main Geograph website. If the app isn't working as expected, check the following:</p>
        <ul>
            <li><strong>Location Access:</strong> If you previously denied location access to the Geograph website, the app won't be able to find you. In Chrome, check "Site 
            settings" within "Settings" to allow it.</li>
            <li><strong>Desktop Site Mode:</strong> If your mobile browser is set to "Request Desktop Site," the app will not scale correctly. Ensure this mode is turned off for 
            <tt>www.geograph.org.uk</tt></li>
        </ul>
    </div>

    <div class="app-info-footer">
        <h3>Good to know</h3>
        <ul>
            <li><strong>Getting Started:</strong> Use the 'Getting Started' button within the app to find more information and access the FAQ.</li>
            <li><strong>Offline Use:</strong> The app requires a connection to open and upload. However, if you have already loaded a map area, it is cached so you can continue to 
            view the map and take photos even if you lose your signal in the field.</li>
            <li><strong>Beta Version:</strong> This app is still in development. We welcome all feedback via the link in the app menu.</li>
            <li><strong>Full Website:</strong> The app focuses on field tools; for advanced features, visit the main site at <a href="http://www.geograph.org.uk">geograph.org.uk</a>.</li>
        </ul>
    </div>
</section>

<script>{literal}
(function() {
        // Check for the 'registered' flag in the URL
        if (window.location.search.indexOf('registered') !== -1) {
            var regNotice = document.getElementById('registration-notice');
            var postRegNotice = document.getElementById('post-reg-notice');
            if (regNotice) regNotice.style.display = 'none';
            if (postRegNotice) postRegNotice.style.display = 'block';
        }

        var ua = navigator.userAgent || navigator.vendor || window.opera;
        var desktop = document.getElementById('desktop-msg');
        var mobile = document.getElementById('mobile-msg');
        var heading = document.getElementById('platform-heading');
        
        var isAndroid = /android/i.test(ua);
        var isIOS = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;
        var isChrome = /Chrome|CriOS/i.test(ua);
        var isSafari = /Safari/i.test(ua) && !/Chrome|CriOS|OPiOS|mercury|FxiOS/i.test(ua);

        if (isAndroid) {
            mobile.style.display = 'block';
            heading.innerHTML = "Use/Install on Android";
            document.getElementById('android-path').style.display = 'block';
            if (isChrome) {
                document.getElementById('is-chrome').style.display = 'block';
            } else {
                document.getElementById('not-chrome').style.display = 'block';
            }
        } 
        else if (isIOS) {
            mobile.style.display = 'block';
            heading.innerHTML = "Use/Install on iOS";
            document.getElementById('ios-path').style.display = 'block';
            if (isSafari) {
                document.getElementById('is-safari').style.display = 'block';
            } else {
                document.getElementById('not-safari').style.display = 'block';
            }
        } 
        else if (/Mobi|Tablet|IPad/i.test(ua)) {
            mobile.style.display = 'block';
        } 
        else {
            desktop.style.display = 'block';
        }
    })();
</script>{/literal}

{include file="_std_end.tpl"}

