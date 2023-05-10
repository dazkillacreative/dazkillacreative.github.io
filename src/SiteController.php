<?php
namespace app\src;

use yii\helpers\Json;
use yii\helpers\Url;
use yii\httpclient\Client;
use Yii;


class SiteController extends \yii\web\Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            new \yii\filters\Cors()
        ];
    }

    public function beforeAction($action)
    {
        if (!($_SERVER['HTTP_ORIGIN'] ??null)) {
            return;
        }

        if (!in_array($_SERVER['HTTP_ORIGIN'], [
            'https://saung-rangon.github.io',
        ])) {
            return;
        }

        return parent::beforeAction($action);
    }

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
        $site = Yii::$app->request->get('s');

        $respon = (new Client)->createRequest()
            ->setUrl(getenv('gh_url') . getenv($site .'_gh_url_invites'))
            ->addHeaders([
                "Accept" => "application/vnd.github+json",
                "Authorization" => "Bearer ". getenv($site . '_gh_token'),
                "X-GitHub-Api-Version" => "2022-11-28",
                "User-Agent" => "blangko-app",
            ])
            ->send()
            ->getContent();

        if ($respon) {
            $respon = Json::decode($respon);

            if (!empty($respon['body'])) {
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

		fetch(`https://yusee.fly.dev/site/create-invite`, {
			headers: {"content-type": "application/x-www-form-urlencoded"},
			body: `title=${title}&nama=${nama}&alamat=${alamat}`,
			method: `post`,
		}).then(r => r.json()).then(data => {
			document.write(`<div style="font-size:20px">Kirim Undangan untuk <b>${title} ${nama}</b> ${alamat ? "di <b>"+alamat+"</b>" : ""}<br>melalui link berikut: <a href="${link}" target="_blank">${link}</s></div>`)
		})
        </script>';
    }

    public function actionGetInvite()
    {
        $title = $alamat = null;
        $nama = Yii::$app->request->get('nama');
        $invite = compact('nama', 'title', 'alamat');
        $invites = $this->getInvites();

        foreach ($invites as $i => $row) {
            if (strpos($row, "$nama::") === 0) {
                @list($nama, $title, $alamat) = explode('::', $row);
                $invite = compact('nama', 'title', 'alamat');
                break;
            }
        }

        header('content-type: application/json');

        return Json::encode($invite);
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

        $site = Yii::$app->request->get('s');

        return (new Client)->createRequest()
            ->setUrl(getenv('gh_url') . getenv($site .'_gh_url_invites'))
            ->setMethod('PATCH')
            ->addHeaders([
                "Accept" => "application/vnd.github+json",
                "Authorization" => "Bearer ". getenv($site . '_gh_token'),
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

        $site = Yii::$app->request->get('s');

        return (new Client)->createRequest()
            ->setUrl(getenv('gh_url') . getenv($site . '_gh_url_comments'))
            ->addHeaders([
                "Accept" => "application/vnd.github+json",
                "Authorization" => "Bearer ". getenv($site . '_gh_token'),
                "X-GitHub-Api-Version" => "2022-11-28",
                "User-Agent" => "blangko-app",
            ])
            ->send()
            ->getContent();
    }

    public function actionPostComment()
    {
        header('content-type: application/json');

        $site = Yii::$app->request->get('s');

        return (new Client)->createRequest()
            ->setUrl(getenv('gh_url') . getenv($site . '_gh_url_comments'))
            ->setMethod('POST')
            ->addHeaders([
                "Accept" => "application/vnd.github+json",
                "Authorization" => "Bearer ". getenv($site . '_gh_token'),
                "X-GitHub-Api-Version" => "2022-11-28",
                "User-Agent" => "blangko-app",
                "Content-Type" => "application/json",
            ])
            ->setContent(Json::encode($_POST))
            ->send()
            ->getContent();
    }
}
