{{* 
Force the browser to reload an image from the server instead of the cache.
based on an answer from http://stackoverflow.com/a/22429796/3343347

Usage: Set $imgUrl to the src url you want to be re-fetched from the server

*}}

<script>
  $(document).ready(
    function() {
      forceImgReload("{{$imgUrl}}");
    }
  );

  {{* 
    * find and return any existing img tags with a matching src url, and set them to an intermediate 
    * src url so they can later be reverted back once the cached version has been updated.
    *}}
  function prepareImagesForReload(srcUrl) {

    var result = $("img[src='" + srcUrl + "']").get();

    for (i = 0; i < result.length; i++) {
      {{* 
        * Set the image to a reloading image, in this case an animated "reloading" svg 
        * Ideally this wont be displayed long enough to matter.
        *}}
      result[i].src = "data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100' preserveAspectRatio='xMidYMid' class='uil-reload'%3E%3Cpath fill='none' class='bk' d='M0 0h100v100H0z'/%3E%3Cg%3E%3Cpath d='M50 15a35 35 0 1 0 24.787 10.213' fill='none' stroke='%23777' stroke-width='12'/%3E%3Cpath d='M50 0v30l16-15L50 0' fill='%23777'/%3E%3CanimateTransform attributeName='transform' type='rotate' from='0 50 50' to='360 50 50' dur='1s' repeatCount='indefinite'/%3E%3C/g%3E%3C/svg%3E";
    }

    return result;
  }

  function restoreImages(srcUrl, imgList) {

    for (i = 0; i < imgList.length; i++) {
      imgList[i].src = srcUrl;
    }
  }

  function forceImgReload(srcUrl) {
    var imgList;
    var step = 0; 
    var iframe = window.document.createElement("iframe");   // Hidden iframe, in which to perform the load+reload.
  
    {{* Callback function, called after iframe load+reload completes (or fails).
        Will be called TWICE unless twostage-mode process is cancelled. (Once after load, once after reload). *}}
    var iframeLoadCallback = function(e) { 

      if (step === 0) { 
        // initial load just completed.  Note that it doesn't actually matter if this load succeeded or not.
      
        step = 1; 
        imgList = prepareImagesForReload(srcUrl); 
        iframe.contentWindow.location.reload(true); // initiate forced-reload!
      
      } else if (step === 1) {
        // forced re-load is done 

        restoreImages(srcUrl, imgList);
        if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
      }
    }

    iframe.style.display = "none";
    window.parent.document.body.appendChild(iframe); {{* NOTE: if this is done AFTER setting src, Firefox MAY fail to fire the load event! *}}
    iframe.addEventListener("load",  iframeLoadCallback, false);
    iframe.addEventListener("error", iframeLoadCallback, false);
    iframe.src = srcUrl;  
  }
</script>
