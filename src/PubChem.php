<?php
declare(strict_types=1);

namespace kdaviesnz\pubchem;



class PubChem
{

    private $chemical;
    private $pubChemId;

    /**
     * PubChem constructor.
     */
    public function __construct(int $pubChemId)
    {
        $client = new \GuzzleHttp\Client();
        $this->fetch($client, $pubChemId);
    }

    public function __toString()
    {
        return $this->chemical;
    }

    private function fetch($client, int $pubChemId)
    {
        $uri = "https://pubchem.ncbi.nlm.nih.gov/rest/pug_view/data/compound/$pubChemId/JSON/?";
        $httpRequest = new \GuzzleHttp\Psr7\Request("GET", $uri, ['Content-Type' => 'application/json; charset=UTF8']);
        $promise = $client->sendAsync($httpRequest)->then(function ($response) use ($pubChemId, $client) {

            $obj = json_decode((String)$response->getBody());

            $molecularFormula = null;

            // Find Section "TOCHeading" => Names and Identifiers
            $sectionIndex = null;
            foreach ($obj->Record->Section as $key=>$section) {
                if (($section->TOCHeading == "Names and Identifiers")) {
                    $sectionIndex = $key;
                    break;
                }
            }

            // 'Molecular Formula'
            $mfsubIndex = null;
            foreach ($obj->Record->Section[$sectionIndex]->Section as $key=>$section) {
                if (($section->TOCHeading == "Molecular Formula")) {
                    $mfsubIndex = $key;
                    break;
                }
            }

            reset($obj->Record->Section[$sectionIndex]->Section);
            $subIndex = null;
            foreach ($obj->Record->Section[$sectionIndex]->Section as $key=>$section) {
                if (($section->TOCHeading == "Computed Descriptors")) {
                    $subIndex = $key;
                    break;
                }
            }

            $molecularFormula = $obj->Record->Section[$sectionIndex]->Section[$mfsubIndex]->Information[0]->StringValue;

            if (empty($molecularFormula)) {
                var_dump("Section index: $sectionIndex");
                var_dump("\$mfsubIndex: $mfsubIndex");
                var_dump("\$subIndex: $subIndex");
                var_dump($obj->Record);
                throw new Exception("Could not get mol form");
            }

            $isomericSMILES = isset($obj->Record->Section[$sectionIndex]->Section[$subIndex]->Section[4]) ?$obj->Record->Section[$sectionIndex]->Section[$subIndex]->Section[4]->Information[0]->StringValue : "";

            $canonicalSMILES = isset($obj->Record->Section[$sectionIndex]->Section[$subIndex]->Section[3]) ?$obj->Record->Section[$sectionIndex]->Section[$subIndex]->Section[3]->Information[0]->StringValue : "";

            $inChIKey =isset($obj->Record->Section[$sectionIndex]->Section[$subIndex]->Section[2]) ?$obj->Record->Section[$sectionIndex]->Section[$subIndex]->Section[2]->Information[0]->StringValue : "";

            $inChI = isset($obj->Record->Section[$sectionIndex]->Section[$subIndex]->Section[1]) ?$obj->Record->Section[$sectionIndex]->Section[$subIndex]->Section[1]->Information[0]->StringValue : "";

            $iUpacName = isset($obj->Record->Section[$sectionIndex]->Section[$subIndex]->Section[0]) ?$obj->Record->Section[$sectionIndex]->Section[$subIndex]->Section[0]->Information[0]->StringValue : "";

            // Related records
            reset($obj->Record->Section);
            $related = array(
                "parents"=>array(),
                "relatedids"=>array(),
                "substanceids"=>array()
            );
            foreach ($obj->Record->Section as $key => $section) {
                if (($section->TOCHeading == "Related Records")) {
                    foreach ($obj->Record->Section[$key]->Section as $skey => $subsection) {
                        if (($subsection->TOCHeading == "Parent Compound")) {
                            $related["parents"][] = $obj->Record->Section[$key]->Section[$skey]->Information[0]->NumValue;
                        }
                        if (($subsection->TOCHeading == "Related Compounds")) {
                            $url = $obj->Record->Section[$key]->Section[$skey]->Information[0]->URL;
                            $related["relatedids"][] = $this->getIds($url, $client);
                        }

                        if (($subsection->TOCHeading == "Substances")) {
                            $url = $subsection->Section[0]->Information[0]->URL;
                            $related["substanceids"][] = $this->getIds($url, $client);
                        }
                    }
                    break;
                }
            }

            $chemicalArr = array(
                "molecularFormula"=>$molecularFormula,
                "isomericSMILES"=>$isomericSMILES,
                "canonicalSMILES"=>$canonicalSMILES,
                "inChIKey"=>$inChIKey,
                "inChI"=>$inChI,
                "iUpacName"=>$iUpacName,
                "related"=>$related
            );

            $this->chemical = json_encode($chemicalArr);


        });
        $promise->wait();
    }

    private function getIds($url, $client) {

        $responseHTML = "";

        $respObj = $client->request("GET", $url, ['Content-Type' => 'text/html; charset=UTF8']);
        sleep(rand(5,8));
        if ($respObj->getStatusCode() != 200 && $respObj->getStatusCode() != 201) {
            // Error handling here
        } else {
            $responseHTML = ((string)$respObj->getBody());
        }

        preg_match_all("/link\_uid\=([0-9]*)/uis", $responseHTML, $matches);

        return $matches[1];

    }

}