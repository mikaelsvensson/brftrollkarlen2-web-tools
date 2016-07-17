<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html" omit-xml-declaration="yes" encoding="utf-8" doctype-public="html"/>

    <xsl:param name="file"/>

    <xsl:template match="/">
        <html lang="sv">
            <head>
                <meta charset="UTF-8"/>
                <meta name="viewport" content="width=device-width, initial-scale=1"/>
                <title></title>
                <!-- Latest compiled and minified CSS -->
                <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css"/>

                <!-- Optional theme -->
                <link rel="stylesheet"
                      href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css"/>
                <link rel="stylesheet" href="style.css"/>
                <!--Load the AJAX API-->
                <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

                <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>

                <script type="text/javascript" src="graphs.js"></script>
            </head>
            <body>
                <div class="container-fluid">
                    <h1>
                        <xsl:value-of select="$file"/>
                    </h1>
                    <div id="overall"></div>
                    <xsl:apply-templates
                            select="//div[@id='ctl00_cphMainFrame_ReportsUC1_ReportsUC1_jqReportTabs_ReportGeneratorUC_upReportGenerator']"
                            mode="root"/>
                </div>

            </body>
        </html>
    </xsl:template>

    <xsl:template match="div" mode="root">
        <table class="table table-striped table-hover table-condensed">
            <xsl:for-each select="descendant::div[contains(@id, '_ReportGeneratorRowUC_pnlReportGeneratorRow') and not(contains(@id, '_ReportGeneratorRowUC_pnlReportGeneratorRowContent'))]">
                <xsl:variable name="childRows"
                              select="descendant::div[contains(@id, '_pnlReportGeneratorChildRows')]/div"/>
                <xsl:if test="$childRows">
                    <tr>
                        <td>SUB
                            <xsl:value-of select="count($childRows)"/>
                        </td>
                        <xsl:for-each select="$childRows[1]//span[contains(@id, '_dtlReportGeneratorColumns')]/span">
                            <xsl:variable name="pos">
                                <xsl:value-of select="position()"/>
                            </xsl:variable>
                            <td>
                                <div class="table-placeholder"
                                     data-table="{generate-id(ancestor::div[contains(@id, '_pnlReportGeneratorChildRows')])}"
                                     data-column="{position()}"></div>
                                <!--
                                                                <xsl:for-each
                                                                        select="$childRows//span[contains(@id, '_dtlReportGeneratorColumns')]/span[position() = $pos]">
                                                                    <xsl:value-of select="."/>
                                                                    <xsl:if test="position() != last()">
                                                                        <br/>
                                                                    </xsl:if>
                                                                </xsl:for-each>
                                -->
                            </td>

                        </xsl:for-each>
                    </tr>
                </xsl:if>
                <xsl:variable name="rows"
                              select="descendant::div[contains(@id, '_pnlReportGeneratorRowContentColumns')]"/>
                <xsl:comment>
                    <xsl:value-of select="count($rows)"/></xsl:comment>
                <xsl:apply-templates select="$rows"
                                     mode="row"/>
            </xsl:for-each>
        </table>
    </xsl:template>

    <xsl:template match="div" mode="row">
        <xsl:variable name="rowType">
            <xsl:choose>
                <xsl:when test="ancestor::div[contains(@id, '_pnlReportGeneratorChildRows')]">
                    small
                </xsl:when>
                <xsl:otherwise>
                    normal
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <tr class="{normalize-space($rowType)}">
            <xsl:apply-templates select="descendant::span[contains(@id, '_lblReportGeneratorCode')]"
                                 mode="rowHeader"/>
            <xsl:apply-templates select="descendant::div[contains(@id, '_upReportGeneratorColumn')]"
                                 mode="cell"/>
        </tr>
    </xsl:template>

    <xsl:template match="span" mode="rowHeader">
        <th>
            <xsl:value-of select="normalize-space(.)"/>
        </th>
    </xsl:template>

    <xsl:template match="div" mode="cell">
        <td class="text-right data-value column-{position()} table-{generate-id(ancestor::div[contains(@id, '_pnlReportGeneratorChildRows')])}">
            <xsl:value-of select="normalize-space(.)"/>
        </td>
    </xsl:template>

</xsl:stylesheet>