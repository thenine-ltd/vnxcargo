
jQuery(document).ready(function ($) {


    var cssCode = CodeMirror.fromTextArea(document.getElementById("custom_css_frontend"), {
        lineNumbers: true,
        lineWrapping: true,
        mode: "css",
        theme: "blackboard",    
        matchBrackets: true,
        autoCloseTags: true,
        autoCloseBrackets: true,
        viewportMargin: Infinity
    });
    

    
    var javascriptCode = CodeMirror.fromTextArea(document.getElementById("custom_js_frontend"), {
        lineNumbers: true,
        lineWrapping: true,
        mode: "javascript",
        theme: "blackboard",    
        matchBrackets: true,
        autoCloseTags: true,
        autoCloseBrackets: true,
        viewportMargin: Infinity
    });

});