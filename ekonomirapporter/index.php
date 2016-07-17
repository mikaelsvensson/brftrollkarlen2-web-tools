    <?php
    $doc = new DOMDocument();
    $file = "Incit Xpand Web.original.htm";
    $doc->loadHTMLFile($file);

    $xslDoc = new DOMDocument();
    $xslDoc->load("drilldown.xsl");

    $proc = new XSLTProcessor();
    $proc->importStylesheet($xslDoc);
    $proc->setParameter('', 'file', $file);
    echo $proc->transformToXML($doc);

    ?>