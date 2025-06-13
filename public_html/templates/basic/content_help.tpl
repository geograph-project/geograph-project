{assign var="page_title" value="Project Information, Guides, Tutorials"}
{assign var="meta_description" value="Listings of various Geograph Information pages, Guides and Tutorials - look here to find out more about the project"}
{include file="_std_begin.tpl"}

<style> {literal}

ul.touchPadding {
	columns: auto 28em;

        li {
                padding-bottom:10px;
		break-inside: avoid;
        }
        li li:last-child {
                padding-bottom:0;
        }
}
.Outdated a {
	color:red;
}
/* Modal Overlay */
.modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 1000; /* Sit on top */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    overflow: auto; /* Enable scroll if content is too long */
    background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
    justify-content: center; /* Center horizontally if using flexbox */
    align-items: center; /* Center vertically if using flexbox */
}

/* When the modal is active/shown */
.modal.show {
    display: flex; /* Use flexbox to center content */
}

/* Modal Content Box */
.modal-content {
    background-color: #fff;
    margin: auto; /* For browsers that don't support flex centering easily, though flex is preferred */
    padding: 20px;
    border: 1px solid #888;
    width: 80%; /* Could be a percentage or a fixed width */
    max-width: 500px; /* Max width for larger screens */
    box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2), 0 6px 20px 0 rgba(0,0,0,0.19);
    position: relative; /* Needed for positioning the close button */
    border-radius: 8px;
    animation-name: animatemodal;
    animation-duration: 0.3s;
}

/* Animation for the modal content */
@keyframes animatemodal {
    from {transform: translateY(-50px); opacity: 0}
    to {transform: translateY(0); opacity: 1}
}

/* Close Button (X) */
.close-button {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    position: absolute;
    top: 10px;
    right: 15px;
    cursor: pointer;
    line-height: 1; /* Adjust vertical alignment */
}

.close-button:hover,
.close-button:focus {
    color: black;
    text-decoration: none;
    outline: none; /* For accessibility */
}

</style>
<script>
function selectTab(classNam) {
	let elements = document.getElementsByClassName('AllLinks');
	for (let i = 0; i < elements.length; i++) {
		elements[i].style.display = 'none';
	}

	elements = document.getElementsByClassName(classNam);
	for (let i = 0; i < elements.length; i++) {
		elements[i].style.display = '';
	}

	elements = document.getElementsByClassName('tabSelected'); //should only be one, by dont have getElementByClassName :)
	for (let i = 0; i < elements.length; i++) {
		elements[i].classList.add('tab');
		elements[i].classList.remove('tabSelected');
	}
	let element = document.getElementById('tab'+classNam);
	element.classList.add('tabSelected');
	element.classList.remove('tab'); //the css doesnt expect both classes!
}

function selectDefaultTab() {
	selectTab('NewContributors');
}
AttachEvent(window,'load',selectDefaultTab,false);

    let modal,closeButton,closeModalButton;

    function openModal(content) {
        if (!content || !content.title)
		return;

        document.getElementById('modalTitle').textContent = content.title;
        document.getElementById('modalContent').textContent = content.content;

        modal.classList.add('show'); // Add 'show' class to make it visible
        modal.setAttribute('aria-hidden', 'false'); // For accessibility
        // Optional: Focus on an element inside the modal for accessibility
        // For example, focus on the close button or the first focusable element
        closeButton.focus();
    }

    function closeModal() {
        modal.classList.remove('show'); // Remove 'show' class to hide it
        modal.setAttribute('aria-hidden', 'true'); // For accessibility
        // Optional: Return focus to the element that opened the modal
    }
    
document.addEventListener('DOMContentLoaded', function() {

    // Get the modal element
    modal = document.getElementById('myModal');

    // Get the <span> element that closes the modal (the 'x')
    closeButton = document.querySelector('.close-button');

    // Get the button inside the modal that closes it
    closeModalButton = document.getElementById('closeModalButton');

    // When the user clicks on the <span> (x), close the modal
    closeButton.addEventListener('click', closeModal);

    // When the user clicks on the "Close" button inside the modal, close it
    closeModalButton.addEventListener('click', closeModal);

    // When the user clicks anywhere outside of the modal content, close it
    // This targets clicks directly on the .modal overlay, not on modal-content
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    // When the user presses the ESC key, close the modal
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal.classList.contains('show')) {
            closeModal();
        }
    });
});
{/literal}

let answers = {$answers};

</script>

    <div id="myModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-describedby="modalContent">
        <div class="modal-content">
            <span class="close-button" aria-label="Close Modal">&times;</span>
            <h2 id="modalTitle">Modal Dialog Title</h2>
            <p id="modalContent">This is the content of your modal dialog box. You can put any HTML here.</p>
            <button id="closeModalButton">Close</button>
        </div>
    </div>

<div class="tabHolder">
	{foreach from=$cats item=cat}
		 <a href="?#{$cat}" class="tab" id="tab{$cat|replace:' ':''}" onclick="selectTab('{$cat|replace:' ':''}'); return false;">{$cat}</a>
	{/foreach}
</div>

<div class="interestBox">
	<h2 style="margin:0">Geograph Project Information, Guides and Tutorials</h2>
</div>

{*---------------------------Search-------------------------*}
{include file="_doc_search.tpl"}
<div id="searchresults"></div>

  
{*---------------------------Articles-------------------------*}

<ul class="touchPadding">
	{foreach from=$list item=item}
		<li class="AllLinks {$item.categories|replace:' ':''|replace:',':' '}{if strpos($item.categories,'Outdated')} Outdated{/if}" style="display:none">
			{if strpos($item.categories,'Obsolete')}
				<span style="text-decoration: line-through;">
			{/if}
			{if $item.answer_id}
				<a href=# onclick="openModal(answers[{$item.answer_id}])">{$item.title|escape:'html'}</a> (faq)
			{else}
				<a title="{$item.extract|default:'View Article'}" href="{$item.url|escape:'html'}">{$item.title|escape:'html'}</a>
				{if $item.source eq 'link'}
					(link)
				{/if}
			{/if}
			{if strpos($item.categories,'Outdated')}
				(may be outdated)
			{/if}
			<small id="att{$lastid+1}"><small style="color:lightgrey">{if $item.user_id}by <a href="/profile/{$item.user_id}" title="View Geograph Profile for {$item.realname}"  style="color:#6699CC">{$item.realname}</a>{/if}
			</small></small>
			{if strpos($item.categories,'Obsolete')}
				</span>
				(Obsolete)
			{/if}
		</li>
	{/foreach}
</ul>

<hr style="color:silver"/>

	<p align="center">Geograph Project Limited is a company limited by guarantee. Registered in England and Wales, number 7473967.<br> Registered office: Dept 1706, 43 Owston Road, Carcroft, Doncaster, South Yorkshire. DN6 8DA. <a href="/article/About-Geograph-page">About Geograph Project</a>.</p>

<script>

</script>


{include file="_std_end.tpl"}

