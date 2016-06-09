<div id="generator" class="hidden overlay">
    <div class="container-fluid">
        <form action="google-document-generator.php" method="get" target="_blank" class="form-horizontal">
            <?php
            const HTML_ENTITY_UNICODE_FOLDER = "&#x1f5c1;";
            $drive_service = new Google_Service_Drive($client);

            // Fetch Google Docs and RFT documents, and also folders.
            $files_list = $drive_service->files->listFiles(array(
//                "q" => "trashed = false",
                "q" => "(mimeType = 'application/vnd.google-apps.document' or mimeType = 'application/rtf' or mimeType = 'application/msword' or mimeType = 'application/vnd.google-apps.folder') and trashed = false",
                "fields" => "files(appProperties,id,name,parents,mimeType),kind,nextPageToken",
                "pageSize" => 1000
            ))->getFiles();

            // Find out the folder id of the user's Google Drive root.
            $roots = [
                $drive_service->files->get("root", array("fields" => "id"))->id
            ];

            // Sort the list of files (and folders) by name.
            usort($files_list, function ($a, $b) {
                return strcmp($a->name, $b->name);
            });

            function process($parent, $indent)
            {
                global $files_list;
                foreach ($files_list as $obj) {
                    $isFileInCurrentFolder = is_array($obj->parents) && in_array($parent, $obj->parents);
                    $isFileGeneratedFromTemplate = isset($obj->appProperties['sourceTemplate']);
                    if ($isFileInCurrentFolder && !$isFileGeneratedFromTemplate) {
                        $isFolder = $obj->mimeType == 'application/vnd.google-apps.folder';
                        $isSelectable = $isFolder;
                        printf('<option value="%s" %s>%s%s</option>',
                            $obj->id,
                            $isSelectable ? 'disabled="disabled"' : '',
                            str_repeat("&nbsp;", $indent * 5),
                            $isFolder ? HTML_ENTITY_UNICODE_FOLDER . $obj->name : $obj->name);

                        if ($isFolder) {
                            process($obj->id, $indent + 1);
                        }
                    }
                }
            }

            ?>
            <h3>Vilken mall vill du anv&auml;nda?</h3>

            <div class="form-group">
                <div class="col-sm-12">
                    <select name="template" class="form-control">
                        <?php
                        foreach ($roots as $root) {
                            process($root, 0);
                        }
                        ?>
                    </select>
                </div>
            </div>

            <p>Dokumentet som skapas kommer att sparas i samma mapp som mallen ligger i. F&ouml;r att det skapade
                dokumentet ska f&aring; ett unikt namn s&aring; kan nedanst&aring;ende parametrar anv&auml;ndas &auml;ven
                i namnet p&aring; mallen. I annat fall kommer aktuellt klockslag anv&auml;ndas f&ouml;r att f&aring;
                fram ett unikt namn.</p>

            <p>Det h&auml;r verktyget kan anv&auml;nda Google Docs-dokument och RTF-dokument (Rich Text Format) som
                mallar. Word-dokument kan tyv&auml;rr inte anv&auml;ndas, men Word-dokument kan enkelt sparas om som
                RTF-dokument inifr&aring;n Word.</p>

            <h3>Vad ska de st&aring; i det nya dokumentet?</h3>

            <p>De h&auml;r orden (parametrarna) kommer att bytas ut i mallen:</p>

            <div id="parameters"></div>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <button type="submit" class="btn btn-primary">Skapa dokument</button>
                    <button type="button" class="btn btn-default" id="button-close">St&auml;ng</button>
                </div>
            </div>
            <p>N&auml;r du skapar ett dokument s&aring; &ouml;ppnas det i ett nytt webbl&auml;sarf&ouml;nster. Det som
                visas &auml;r PDF-versionen av dokumentet som sparas p&aring; din Google Drive.</p>

            <p>Du kan ocks&aring; anv&auml;nda dessa parametrar:</p>
            <ul class="list-unstyled">
                <?php
                foreach ($defaultParams as $key => $value) {
                    printf('<li><code>%s</code>: %s</li>', $key, $value);
                }
                ?>
            </ul>
        </form>
    </div>
</div>
