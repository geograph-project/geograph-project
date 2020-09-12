
 <link rel="stylesheet" type="text/css" href="{"/templates/basic/css/downloader.css"|revision}" />

 <button id="download-button">Download images above in .zip File</button>

    <script type="text/javascript" src="{$static_host}/js/jszip.min.js"></script>
    <script type="text/javascript" src="{$static_host}/js/jszip-utils.js"></script>
    <script type="text/javascript" src="{$static_host}/js/FileSaver.js"></script>
    <script type="text/javascript" src="{$static_host}/js/downloader.js"></script>

<div class="progress hide" id="progress_bar">
    <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">
    </div>
</div>

<p class="hide" id="result"></p>


