{assign var="page_title" value="Report a concern"}
{include file="_std_begin.tpl"}

<h2>Report a Concern</h2>

{if $done}
<div class="interestBox" style="margin-bottom:300px">
	<b>Your question has been received</b>, thank you. A member of the team will take a look and get back to you.
</div>
{else}

<p>If you see harmful or potentially illegal content on any Geograph page, please tell us about it here and click 'Submit Report'.
We'll know which page you were on when you opened this form and will review it as soon as possible. For general help questions or
landowner concerns, please use the "Contact us" link on the main menu.</p>

{literal}
<style type="text/css">
.report-form .form-group {
    display: flex;
    flex-wrap: wrap; /* Allows label to wrap above input on small screens */
    margin-bottom: 1em;
    align-items: center; /* Vertically align label and input group */
}

.report-form .form-label {
    flex-basis: 150px; /* Set a basis for the label width */
    margin-right: 1em;
    font-weight: bold;
    padding-top: 0.3em; /* Align with text box content */
}

.report-form .input-group {
    flex-grow: 1; /* Allows the input group to take remaining space */
    display: flex;
    flex-direction: column; /* Stack input and small text if any */
}

.report-form .input-group input[type="text"],
.report-form .input-group input[type="email"],
.report-form .input-group textarea {
    width: 100%; /* Make inputs fill their container */
    max-width: 400px; /* Optional: prevent inputs from becoming too wide */
    box-sizing: border-box; /* Include padding and border in the element's total width and height */
    padding: 0.4em; /* Added padding for better appearance */
    border: 1px solid #ccc; /* Added border for better appearance */
    border-radius: 3px; /* Added border-radius for better appearance */
}

.report-form .input-group textarea {
    min-height: 80px; /* Ensure textarea is a reasonable size */
    resize: vertical; /* Allow vertical resizing */
}

.report-form .error-marker {
    color: red;
    margin-left: 0.25em;
    font-weight: bold;
}

.report-form .input-group small {
    font-size: 0.85em;
    color: #555;
    margin-top: 0.25em;
}

.report-form .form-buttons {
    margin-top: 1.5em;
    padding-left: 160px; /* Align with inputs if labels are 150px + 1em margin */
}

.report-form .form-buttons input.button {
    margin-right: 0.5em;
    padding: 0.5em 1em; /* Added padding for better appearance */
    border-radius: 3px; /* Added border-radius for better appearance */
}

/* Responsive adjustments for smaller screens */
@media (max-width: 767px) {
    .report-form .form-group {
        flex-direction: column; /* Stack label and input group */
        align-items: flex-start; /* Align items to the start */
    }

    .report-form .form-label {
        flex-basis: auto; /* Allow label to take its own width */
        margin-right: 0;
        margin-bottom: 0.5em; /* Space between label and input */
        padding-top: 0;
    }

    .report-form .input-group input[type="text"],
    .report-form .input-group input[type="email"],
    .report-form .input-group textarea {
        max-width: 100%; /* Allow inputs to use full width */
    }

    .report-form .form-buttons {
        padding-left: 0; /* Remove padding when stacked */
    }
}
</style>
{/literal}

<form action="https://company.geograph.org.uk/support/open.php" method="POST" enctype="multipart/form-data" class="report-form">
{dynamic}
    <input type="hidden" name="topicId" value="15">
    <input type="hidden" name="ref" value="{$referring_page|escape:'html'}"/>
    <input type="hidden" name="user_id" value="{$user->user_id}"/>
    <input type="hidden" name="gtok" value="{$gtok}"/>

    <div class="form-group">
        <label for="name" class="form-label">Full Name:</label>
        <div class="input-group">
            <input type="text" id="name" name="name" size="25" value="{$user->realname|escape:'html'}" required>
        </div>
    </div>

    <div class="form-group">
        <label for="email" class="form-label">Email Address:</label>
        <div class="input-group">
            <input type="email" id="email" name="email" size="25" value="{$user->email|escape:'html'}" required>
		 <small>(using this form will reveal your email address to support representatives)</small>
        </div>
    </div>
{/dynamic}

    <div class="form-group">
        <label for="subject" class="form-label">Subject:</label>
        <div class="input-group">
            <input type="text" id="subject" name="subject" size="35" value="Report a concern" required>
        </div>
    </div>

    <div class="form-group">
        <label for="message" class="form-label">Message:</label>
        <div class="input-group">
            <textarea id="message" name="message" cols="35" rows="8" wrap="soft" placeholder="enter your message here" required></textarea>
        </div>
    </div>

    <div class="form-buttons">
        <input class="button" type="submit" name="submit_x" value="Submit Report">
        <input class="button" type="button" name="cancel" value="Cancel" onClick='window.location.href="/"'>
    </div>
</form>

<div style="text-align:right; margin-top: 1em;"><a id="powered_by" target="_blank" href="http://osticket.com"><img src="https://company.geograph.org.uk/support/assets/default/images/poweredby.png" width="126" height="23" alt="Powered by osTicket"></a></div>

<br style="clear:both"/>

{/if}

{include file="_std_end.tpl"}
