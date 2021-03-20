jQuery(function ($) {
    "use strict";

    var Promise = window.Promise;
    if (!Promise) {
        Promise = JSZip.external.Promise;
    }

    /**
     * Reset the message.
     */
    function resetMessage () {
        $("#result")
        .removeClass()
        .text("");
    }
    /**
     * show a successful message.
     * @param {String} text the text to show.
     */
    function showMessage(text) {
        resetMessage();
        $("#result")
        .addClass("alert alert-success")
        .text(text);
    }
    /**
     * show an error message.
     * @param {String} text the text to show.
     */
    function showError(text) {
        resetMessage();
        $("#result")
        .addClass("alert alert-danger")
        .text(text);
    }
    /**
     * Update the progress bar.
     * @param {Integer} percent the current percent
     */
    function updatePercent(percent) {
        $("#progress_bar").removeClass("hide")
        .find(".progress-bar")
        .attr("aria-valuenow", percent)
        .css({
            width : percent + "%"
        });
    }

    /**
     * Fetch the content and return the associated promise.
     * @param {String} url the url of the content to fetch.
     * @return {Promise} the promise containing the data.
     */
    function urlToPromise(url) {
        return new Promise(function(resolve, reject) {
            JSZipUtils.getBinaryContent(url, function (err, data) {
                if(err) {
                    reject(err);
                } else {
                    resolve(data);
                }
            });
        });
    }

    if(!JSZip.support.blob) {
        showError("Sorry, download option, only available on modern browsers!");
	$("#download-button").prop('disabled',true);
        return;
    }

    $("#download-button").on("click", function () {

        resetMessage();

	showMessage("GENERATING FILE, PLEASE WAIT!");

        var zip = new JSZip();

        //find all images to download
        $("a.download").each(function () {
            var $this = $(this);
            var url = $this.attr("href");
            var filename = $this.data("filename");
	    if (!filename && ( 
			       (m = url.match(/id=(\d+)/))
			    || (m = url.match(/\d\/(\d{6,})_/))
                            || (m = url.match(/photo\/(\d+)/)) 
                ) )
		filename = "geograph-"+m[1]+".jpg";
            zip.file(filename, urlToPromise(url), {binary:true});
        });

        // when everything has been downloaded, we can trigger the dl
        zip.generateAsync({type:"blob"}, function updateCallback(metadata) {
            var msg = "GENERATING FILE, PLEASE WAIT! (" + metadata.percent.toFixed(2) + " %";
            if(metadata.currentFile) {
                msg += ", current file = " + metadata.currentFile;
            }
	    msg += ")";
            showMessage(msg);
            updatePercent(metadata.percent|0);
        })
        .then(function callback(blob) {

            // see FileSaver.js
            saveAs(blob, "geograph-images.zip");

            showMessage("File 'geograph-images.zip' created, and downloading now.");
        }, function (e) {
            showError(e);
        });

        return false;
    });
});

// vim: set shiftwidth=4 softtabstop=4:
