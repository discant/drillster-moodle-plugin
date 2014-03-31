//DEFAULT functions
if (typeof log != 'function')
{
    function log(i)
    {
        try {
            console.log(i)
        } catch (e) {
        }
    }
    log('INIT: log function');
}
//The yui
YUI().use("node-base", 'event', 'anim', function(Y)
{
    function IsValidImageUrl(url, callback)
    {
        var img = new Image();
        img.onerror = function()
        {
            callback(url, false);
        }
        img.onload = function()
        {
            callback(url, true);
        }
        img.src = url
    }
    //wait to page is loaded
    Y.on("load", function() {

        var drillcode = '';
        //check the rel
        if (Y.one('#drillHolder') !== null)
        {
            //check if we have a valid rel return
            drillcode = Y.one('#drillHolder').getAttribute('rel');

            if (drillcode !== '')
            {
                //do access login call
                log(drillcode);
                //little tweak to check if image with access code is loaded
                IsValidImageUrl(Y.one('#drillAuth').getAttribute('src'), buildIframe);
            }
        }
        function buildIframe(url, answer)
        {
            if (answer)
            {
                log('BUILD Iframe');
                Y.one('#drillHolder').setContent('<iframe id="widget" src="' + drillcode + '"  scrolling="no" frameborder="0"></iframe>');
            }
        }
    })
})