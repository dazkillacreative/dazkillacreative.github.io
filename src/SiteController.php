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
            'http://localhost:8000',
            'https://saung-rangon.github.io',
            'https://wedding-akbar-retha.github.io',
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

        $hdrs = [
            "Accept" => "application/vnd.github+json",
            "Authorization" => "Bearer ". getenv($site . '_gh_token'),
            "X-GitHub-Api-Version" => "2022-11-28",
            "User-Agent" => "blangko-app",
        ];

        Yii::debug('GH API Req: '.print_r([
            'url' => $url = getenv('gh_url') . getenv($site .'_gh_url_invites'),
            'headers' => $hdrs
        ],1), __METHOD__);

        $respon = (new Client)->createRequest()
            ->setUrl($url)
            ->addHeaders($hdrs)
            ->send()
            ->getContent();

        if ($respon) {
            $respon = Json::decode($respon);

            Yii::debug('GH API Res: '.print_r($respon, 1), __METHOD__);
    
            if (!empty($respon['body'])) {
                return explode("\n", $respon['body']);
            }
        }
        else {
            Yii::debug('GH API Res: '.$respon, __METHOD__);
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

        return $this->asJson($invite);
    }

    public function actionCreateInvite()
    {
        $invites = $this->getInvites();
        $new = true;

        foreach ($invites as $i => $row) {
            if (strpos($row, $_POST['nama']."::") === 0) {
                $invites[$i] = "$_POST[nama]::$_POST[title]::$_POST[alamat]";
                $new = false;
                break;
            }
        }

        if ($new) {
            $invites[] = "$_POST[nama]::$_POST[title]::$_POST[alamat]";
        }

        $site = Yii::$app->request->get('s');

        $hdrs = [
            "Accept" => "application/vnd.github+json",
            "Authorization" => "Bearer ". getenv($site . '_gh_token'),
            "X-GitHub-Api-Version" => "2022-11-28",
            "User-Agent" => "blangko-app",
            "Content-Type" => "application/json",
        ];

        Yii::debug('GH API Req: '.print_r([
            'url' => $url = getenv('gh_url') . getenv($site .'_gh_url_invites'),
            'headers' => $hdrs,
            'data' => $data = new \ArrayObject([
                'body' => implode("\n", $invites)
            ])
        ],1), __METHOD__);

        $respon = (new Client)->createRequest()
            ->setUrl($url)
            ->setMethod('PATCH')
            ->addHeaders($hdrs)
            ->setContent(Json::encode($data))
            ->send()
            ->getContent();

        if ($respon) {
            $data = Json::decode($respon);
            Yii::debug('GH API Res: '.print_r($data, 1), __METHOD__);
        }
        else {
            Yii::debug('GH API Res: '.$respon, __METHOD__);
        }

        header('Content-Type: application/json');
        return $respon;
    }

    public function actionGetComments()
    {
        $site = Yii::$app->request->get('s');
        $page = Yii::$app->request->get('page')?: 1;

        $hdrs = [
            "Accept" => "application/vnd.github+json",
            "Authorization" => "Bearer ". getenv($site . '_gh_token'),
            "X-GitHub-Api-Version" => "2022-11-28",
            "User-Agent" => "blangko-app",
        ];

        Yii::debug('GH API Req: '.print_r([
            'url' => $url = getenv('gh_url') . getenv($site .'_gh_url_comments') . '/comments?page=' . $page,
            'headers' => $hdrs,
        ],1), __METHOD__);

        $respon = (new Client)->createRequest()
            ->setUrl($url)
            ->addHeaders($hdrs)
            ->send()
            ->getContent();

        if ($respon) {
            $data = Json::decode($respon);
            Yii::debug('GH API Res: '.print_r($data, 1), __METHOD__);
        }
        else {
            Yii::debug('GH API Res: '.$respon, __METHOD__);
        }
    
        header('Content-Type: application/json');
        return $respon;
    }

    public function actionPostComment()
    {
        $site = Yii::$app->request->get('s');

        $hdrs = [
            "Accept" => "application/vnd.github+json",
            "Authorization" => "Bearer ". getenv($site . '_gh_token'),
            "X-GitHub-Api-Version" => "2022-11-28",
            "User-Agent" => "blangko-app",
            "Content-Type" => "application/json",
        ];

        Yii::debug('GH API Req: '.print_r([
            'url' => $url = getenv('gh_url') . getenv($site .'_gh_url_comments') . '/comments',
            'headers' => $hdrs,
            'data' => $_POST
        ],1), __METHOD__);

        $respon = (new Client)->createRequest()
            ->setUrl($url)
            ->setMethod('POST')
            ->addHeaders($hdrs)
            ->setContent(Json::encode($_POST))
            ->send()
            ->getContent();

        if ($respon) {
            $data = Json::decode($respon);
            Yii::debug('GH API Res: '.print_r($data, 1), __METHOD__);
        }
        else {
            Yii::debug('GH API Res: '.$respon, __METHOD__);
        }

        header('Content-Type: application/json');
        return $respon;
    }
}
