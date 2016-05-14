(function () {
    $(document).ready(function () {
        $("td.Namn").each(function (e) {
            $(this).append($("<a>").text("skapa dokument").attr("href", "google-document-generator.php?NAMN=" + encodeURIComponent($(this).text())));
        });
    });
})();