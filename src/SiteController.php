<?php
namespace app\src;

use yii\helpers\Json;

define('TOKEN', getenv('GH_TOKEN'));
define('URL', 'https://api.github.com/repos'. getenv('GH_ISSUE_URL') .'/comments');

class SiteController extends \yii\web\Controller
{

    public $enableCsrfValidation = false;

    public function actionIndex()
    {
        return \Yii::$app->view->render('@app/src/site.php');
    }

    public function actionInvite()
    {
        return '<script>
            const title = prompt("Titel:\nKeluarga/Bapak/Ibu/Saudara/Saudari/Team");
            const nama = prompt("Nama:");
            const alamat = prompt("Alamat");
            const link = `'. \yii\helpers\Url::base(true) .'?`+ btoa(title +"::"+ nama +"::"+ alamat);
            document.write(`<div style="font-size:20px">Kirim Undangan untuk <b>${title} ${nama}</b> ${alamat ? "di <b>"+alamat+"</b>" : ""}<br>melalui link berikut: <a href="${link}" target="_blank">${link}</s></div>`)
        </script>';
    }

    public function actionGetComments()
    {
        header('content-type: application/json');
        return (new \yii\httpclient\Client)->createRequest()
            ->setUrl(URL)
            ->addHeaders([
                "Accept" => "application/vnd.github+json",
                "Authorization" => "Bearer ". TOKEN,
                "X-GitHub-Api-Version" => "2022-11-28",
                "User-Agent" => "blangko-app",
            ])
            ->send()
            ->getContent();
    }

    public function actionPostComment()
    {
        header('content-type: application/json');
        return (new \yii\httpclient\Client)->createRequest()
            ->setUrl(URL)
            ->setMethod('POST')
            ->addHeaders([
                "Accept" => "application/vnd.github+json",
                "Authorization" => "Bearer ". TOKEN,
                "X-GitHub-Api-Version" => "2022-11-28",
                "User-Agent" => "blangko-app",
                "Content-Type" => "application/json",
            ])
            ->setContent(Json::encode($_POST))
            ->send()
            ->getContent();
    }
}
