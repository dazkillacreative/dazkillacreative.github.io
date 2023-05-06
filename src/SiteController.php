<?php
namespace app\src;

use yii\helpers\Json;
use yii\helpers\Url;
use yii\httpclient\Client;

define('TOKEN', getenv('GH_TOKEN'));
define('URL_COMMENTS', 'https://api.github.com/repos'. getenv('GH_URL_COMMENTS') .'/comments');
define('URL_INVITES', 'https://api.github.com/repos'. getenv('GH_URL_INVITES'));

class SiteController extends \yii\web\Controller
{

    public $enableCsrfValidation = false;

    public function actionIndex()
    {
        $nama = $title = $alamat = null;
        $invite = compact('nama', 'title', 'alamat');
        $invites = $this->getInvites();
        $nama = $_GET['to'] ??null;

        foreach ($invites as $i => $row) {
            if (strpos($row, "$nama::") === 0) {
                @list($nama, $title, $alamat) = explode('::', $row);
                $invite = compact('nama', 'title', 'alamat');
                break;
            }
        }

        return \Yii::$app->view->render('@app/src/site.php', compact('invite'));
    }

    protected function getInvites()
    {
        $respon = (new Client)->createRequest()
            ->setUrl(URL_INVITES)
            ->addHeaders([
                "Accept" => "application/vnd.github+json",
                "Authorization" => "Bearer ". TOKEN,
                "X-GitHub-Api-Version" => "2022-11-28",
                "User-Agent" => "blangko-app",
            ])
            ->send()
            ->getContent();

        if ($respon) {
            $respon = Json::decode($respon);

            if ($respon['body']) {
                return explode("\n", $respon['body']);
            }
        }

        return [];
    }

    public function actionInvite()
    {
        return '<script>
            const title = prompt("Titel:\nKeluarga/Bapak/Ibu/Saudara/Saudari/Team");
            const nama = prompt("Nama:");
            const alamat = prompt("Alamat");
            const link = `'. Url::base(true) .'?to=`+ nama.replaceAll(" ","+");

            fetch(`'. Url::base() .'/site/create-invite`, {
				headers: {"content-type": "application/x-www-form-urlencoded"},
				body: `title=${title}&nama=${nama}&alamat=${alamat}`,
                method: `post`,
			}).then(r => r.json()).then(data => {
                document.write(`<div style="font-size:20px">Kirim Undangan untuk <b>${title} ${nama}</b> ${alamat ? "di <b>"+alamat+"</b>" : ""}<br>melalui link berikut: <a href="${link}" target="_blank">${link}</s></div>`)
            })
        </script>';
    }

    public function actionCreateInvite()
    {
        header('content-type: application/json');

        $data = $this->getInvites();
        $new = true;

        foreach ($data as $i => $row) {
            if (strpos($row, $_POST['nama']."::") === 0) {
                $data[$i] = "$_POST[nama]::$_POST[title]::$_POST[alamat]";
                $new = false;
                break;
            }
        }

        if ($new) {
            $data[] = "$_POST[nama]::$_POST[title]::$_POST[alamat]";
        }

        return (new Client)->createRequest()
            ->setUrl(URL_INVITES)
            ->setMethod('PATCH')
            ->addHeaders([
                "Accept" => "application/vnd.github+json",
                "Authorization" => "Bearer ". TOKEN,
                "X-GitHub-Api-Version" => "2022-11-28",
                "User-Agent" => "blangko-app",
                "Content-Type" => "application/json",
            ])
            ->setContent(Json::encode([
                'body' => implode("\n", $data)
            ]))
            ->send()
            ->getContent();
    }

    public function actionGetComments()
    {
        header('content-type: application/json');
        return (new Client)->createRequest()
            ->setUrl(URL_COMMENTS)
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
        return (new Client)->createRequest()
            ->setUrl(URL_COMMENTS)
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
