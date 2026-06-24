{assign var="page_title" value="GPX Export"}
{include file="_std_begin.tpl"}

<style>{literal}
  .gpx-form {
    background-color: #e9e9e9;
    padding: 15px;
    margin-bottom: 20px;
  }
  .gpx-form fieldset {
    border: 1px solid #ccc;
    padding: 10px 15px;
    margin-bottom: 15px;
    background: #eeeeee;
    border-radius:10px;
  }
  .gpx-form legend {
    font-weight: bold;
    padding: 0 5px;
  }
  .gpx-form button[type="submit"] {
    display: block;
    margin-top: 10px;
    padding: 6px 12px;
    cursor: pointer;
  }
  .form-hint, .form-note {
    color: blue;
  }

/* Two-column layout rules for Form 3 */
  .form-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
  }
  .form-main-col {
    flex: 1 1 500px; /* Takes up left side, wraps if screen is too narrow */
  }
  .form-side-col {
    flex: 1 1 300px; /* Takes up right side */
    background: #ffffff;
    border: 1px solid #ccc;
    padding: 15px;
    border-radius: 4px;
  }
  .form-side-col h4 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
  }

</style>{/literal}

<h2>GPX Export</h2>

<p>Use this page to download a {external href="http://www.topografix.com/gpx.asp" text="GPX"} file to load into your mapping program and/or GPS receiver. This is ideal for creating a high tech version of the printable Check Sheet for when you go paperless.</p>

<p>Note: We have <a href="/mapper/combined.php">online coverage maps</a> that plot these coverage data on interactive maps, saving you having to manually download.</p>

<hr>

{dynamic}
{if $errormsg}
	<p><b>{$errormsg}</b></p>
{/if}

<h3>1. Community Coverage</h3>
<form method="get" action="{$script_name}" name=form1 class="gpx-form">
	<fieldset>
		<legend>Location Filter</legend>
		<p>
			<label for="gridref">Center grid square:</label> 
			<input id="gridref" type="text" name="gridref" value="{$gridref|escape:'html'}" size="8"
			{literal} pattern="^[A-Z]{1,2}\d{2,4}$" title="enter a valid gridref" required/>{/literal}
			<span class="form-hint">(Can use SH34 for SH3545)</span>
		</p>
		
		<p>
			<label>
				<input id="limit_distance" type="radio" name="limit" value="distance"{if $limit == 'distance'} checked="checked"{/if}/>
				Radius
			</label>
			<select name="distance" id="distance" size="1"> 
				{html_options values=$distances output=$distances selected=$distance}
			</select> km
			<br>
			<i>or</i>
			<br>
			<label>
				<input id="limit_points" type="radio" name="limit" value="points"{if $limit == 'points'} checked="checked"{/if}/>
				Number of points
			</label>
			<select name="points" id="points" size="1"> 
				{html_options values=$distances output=$distances selected=$points}
			</select>
		</p>
	</fieldset>

	<fieldset>
		<legend>What to Download</legend>
		<p>
			<label for="download_type_legacy">Download Squares:</label> 
			<select name="type" id="download_type_legacy">
				{html_options options=$types selected=$type}
			</select>
		</p>
	</fieldset>
	
	<button type="submit" onclick="handleGPXSubmit(event, this)">Download GPX file...</button>
</form>
{/dynamic}

<hr>

{if $user->registered}

    <h3>2. Combined Community/Personalized Coverage (beta)</h3>

    <p>Our Scout mapping layer assigns a single status to a square based on a blend of community and your own personal coverage, which is replicated here as a GPX download.</p>

    <img align="right" src="https://t0.geograph.org.uk/tile.php?map=toVJ5oOXXJ0oX.VJLo-NJFoOXXJfo-lNXJqo-NMJL5405ow4uZZhhNZVhlVuOX" alt="Map showing Myriads">

    <form action="{$script_name}" method="get" name=form2 class="gpx-form">
	<input type=hidden name=scout value=1>
      <fieldset>
        <legend>Choose squares to include</legend>
        
        <label>
          <input type="checkbox" name="unphotographed" checked>
          Ungeographed (by anyone)
        </label><br>
        
        <label>
          <input type="checkbox" name="fewPhotos" checked>
          Few Photos (low number of images for the area)
        </label><br>
        
        <label>
          <input type="checkbox" name="noRecent" checked>
          No Recent (no Geographs in last 5 years)
        </label><br>
        
        <label>
          <input type="checkbox" name="personal" checked>
          Personal Available (you not Geographed)
        </label><br>
        
        <label>
          <input type="checkbox" name="personalUpdate" checked>
          Personal Redo (&gt;5yrs) (have but not recently)
        </label><br>
        
        <label>
          <input type="checkbox" name="done">
          Done (have visited recently)
        </label>

	<hr>
	Each square is assigned to one of the above statues, in the order above even if technically matches multiple. The GPX file will be coloured pins, one per square.
      </fieldset>

      <fieldset>
        <legend>Coverage</legend>
        Specify <strong>UPTO 10</strong> squares, either Myriad (eg TQ, SH, or N for Ireland squares), or Hectad: TQ74, or N52. Can mix and match.</p>
        
        <label for="squares_input">Squares:</label>
        <input 
          type="text" 
          id="squares_input" 
          name="squares" 
          size=40
	  placeholder="eg: TQ,TV,SZ,SU"
          {literal}pattern="^[A-Z]{1,2}(\d{2}|)([\s ;,]+[A-Z]{1,2}(\d{2}|))*$" {/literal}
          title="Please enter up to 10 valid Myriad or Hectad codes separated by spaces, commas, or semicolons."
          required
        >
      </fieldset>

      <button type="submit" onclick="handleGPXSubmit(event, this)">Download Scout GPX file...</button>

	Reminder: can be upto 10,000 squares per myriad, so this can produce VERY large files. Make sure your software can safely load large files.<hr>
	For most contributors the 'Personal Available' will be the largest layer; if the file is too large inverting to get your DONE squares instead, will typicall be a much smaller file.

    </form>

    <hr>

    <h3>3. Download Search Results</h3>

	<p>Coming soon!</p>
<form action="{$script_name}" method="get" class="gpx-form" style="display:none">
      <div class="form-grid">
        
        <div class="form-main-col">
          <fieldset>
            <legend>Filter</legend>
            <label for="search_query">Keywords:</label>
            <input type="search" id="search_query" name="query" placeholder="e.g., castle year:2020" style="width: 100%; max-width: 400px; box-sizing: border-box;">
            <br><br>
            
            <label>
              <input type="checkbox" id="my_images_toggle" onchange="toggleUserFilter(this, {$user->user_id})">
              My Images Only
            </label>
          </fieldset>

          <fieldset>
            <legend>Position Plotted</legend>
            <label>
              <input type="radio" name="position_type" value="subject" checked>
              Subject Position
            </label><br>
            <label>
              <input type="radio" name="position_type" value="photographer">
              Photographer Position <span class="form-hint">(ignores photos without a recorded camera location)</span>
            </label>
          </fieldset>

          <fieldset>
            <legend>What to Download</legend>
            <label>
              <input type="radio" name="download_type" value="individual">
              Individual Photos <span class="form-hint">(10k limit)</span>
            </label><br>
            
            <label>
              <input type="radio" name="download_type" value="one_per_grid" checked>
              One image per gridsquare <span class="form-hint">(10k limit)</span>
            </label><br>
            
            <label>
              <input type="radio" name="download_type" value="one_per_hectad">
              One image per Hectad <span class="form-hint">(3k limit &ndash; enough for all)</span>
            </label><br>
            
            <label>
              <input type="radio" name="download_type" value="one_per_myriad">
              One image per Myriad <span class="form-hint">(enough for all)</span>
            </label>
            
            <p class="form-note"><em>Note: The specific image chosen per square is arbitrary.</em></p>
          </fieldset>

          <button type="submit" onclick="handleGPXSubmit(event, this)">Download Results GPX file...</button>
        </div>

        <div class="form-side-col">
          <h4>Quick Query Reference</h4>
          <ul style="padding-left: 20px; margin-bottom: 15px;">
            <li><code>[tag]</code>  <span class="form-hint">Put tags in [] brackets</span></li>
            <li><code>year:2000</code> <span class="form-hint">(images taken in that specific year)</span></li>
            <li><code>month:201007</code> <span class="form-hint">(images taken in that specific month)</span></li>
            <li><code>status:geograph</code> <span class="form-hint">(geographs only)</span></li>
            <li><code>ftf:1</code> <span class="form-hint">(First to Find / first geographs)</span></li>
            <li><code>myriad:TQ</code> <span class="form-hint">(filter by 100km square)</span></li>
            <li><code>hectad:TQ54</code> <span class="form-hint">(filter by 10km square)</span></li>

	    <li><code>myriad:TQ|TV|SZ|SU</code> <span class="form-hint">(OR filter multiple squares, as long as total results stay under the 10k limit)</span></li>

          </ul>
          
          <p>Combine multiple keywords freely. General search terms are best placed at the start:</p>
          
          <strong style="display:block; margin-top:10px;">Examples:</strong>
          <ul style="padding-left: 20px; margin-top: 5px;">
            <li><code class="example">user{$user->user_id} year:2010 ftf:1</code></li>
            <li><code class="example">user{$user->user_id} [panorama] year:2025</code></li>
            <li><code class="example">user{$user->user_id} castle year:2025</code></li>
          </ul>

          <p style="border-top: 1px solid #eee; padding-top: 10px; margin-bottom:">
            &rarr; <a href="https://www.geograph.org.uk/article/Keyword-Searching-in-the-Browser" target="_blank" rel="noopener">Full Reference</a> <span class="form-hint">(same syntax as Image Browser)</span>
          </p>

        </div>

      </div><p class="form-disclaimer" style="margin-top: 15px;">
        <strong>Note:</strong> This form is optimized for retrieving details of locations/squares that <strong>HAVE</strong> been photographed. It cannot list unphotographed squares (i.e., areas that do not match the filter criteria).
      </p>
    </form>

{else}
	<p><a href="?login=1">Login</a> to get a personalied coverage file</p>
{/if}

<script>{literal}
function toggleUserFilter(checkbox, user_id) {
  const queryInput = document.getElementById('search_query');
  const userTag = "user" + user_id + " ";
  
  if (checkbox.checked) {
    if (!queryInput.value.startsWith(userTag)) {
      queryInput.value = userTag + queryInput.value;
    }
  } else {
    if (queryInput.value.startsWith(userTag)) {
      queryInput.value = queryInput.value.replace(userTag, "");
    }
  }
}

function handleGPXSubmit(event, button) {
    const form = button.form;
    if (!form) return true;

    // 1. Update UI to show something is happening
    const originalText = button.innerText;
    button.innerText = "Generating GPX... Please wait...";
    
    // Defer disabling by 50ms so the browser registers the form submit action
    setTimeout(() => {
        button.disabled = true;
    }, 50);

    // 2. Clear out any previous change listeners to avoid stacking them
    if (form._changeResetCleanup) {
        form._changeResetCleanup();
    }

    // 3. Define the reset function
    const resetButton = () => {
        button.disabled = false;
        button.innerText = originalText;
        if (form._changeResetCleanup) {
            form._changeResetCleanup();
        }
    };

    // 4. Setup Form Change Listener: If they change inputs, unlock instantly!
    const changeHandler = () => {
        resetButton();
    };
    form.addEventListener('input', changeHandler);
    form.addEventListener('change', changeHandler);

    // Save cleanup references directly on the form node
    form._changeResetCleanup = () => {
        form.removeEventListener('input', changeHandler);
        form.removeEventListener('change', changeHandler);
        clearTimeout(safetyTimeout);
        form._changeResetCleanup = null;
    };

    // 5. Safety backup: Still auto-unlock after 30 seconds if they do nothing
    const safetyTimeout = setTimeout(resetButton, 30000);

    return true;
}

</script>{/literal}

<hr>

<p style="background-color:lightgreen;padding:10px;">Loading these GPX files into Google Earth or other similar software, will produce coverage maps. You can also load actual images into Google Earth, using <a href="/kml.php">KML files</a>.</p>

<p style="background-color:lightblue;padding:10px;">You can also download the images from a set of <a href="/search.php">search results</a> and/or the <a title="Latest Images in GPX format" href="/feed/recent.gpx">latest uploads</a> in GPX format. </p>

{include file="_std_end.tpl"}
