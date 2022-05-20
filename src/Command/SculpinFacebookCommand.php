<?php

declare(strict_types=1);

namespace TechNikh\SculpinFacebookBundle\Command;

//use Contentful\Delivery\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;


final class SculpinFacebookCommand extends Command
{
    public const SUCCESS = 0;
    public const FAILURE = 1;

    //private Client\ClientInterface $contetful;
    private \Facebook\Facebook $fb;

    protected function configure()
    {
        $app_id = getenv('app_id');
        $app_secret = getenv('app_secret');
        $default_access_token = getenv('default_access_token');


        $this
            ->setName('facebook:fetch')
            ->setDescription('Fetch Contentful data.')
            ->setHelp("The <info>contentful:fetch</info> command fetches contentful data and create files locally.");
        //->setContentfulClient(new Client($contentfulToken, $contentfulSpaceId));
        $fb = new \Facebook\Facebook([
            'app_id' => $app_id,
            'app_secret' => $app_secret,
            'default_graph_version' => 'v12.0',
            'default_access_token' => $default_access_token, // optional
        ]);
        $this->fb = $fb;


        try {
            // Get the \Facebook\GraphNodes\GraphUser object for the current user.
            // If you provided a 'default_access_token', the '{access-token}' is optional.
            $response = $fb->get('/me', '');
            $me = $response->getGraphUser();
            echo 'Logged in as ' . $me->getName();
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            //exit;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            echo '1Facebook SDK returned an error: ' . $e->getMessage();
            //exit;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Albums
        $response = $this->fb->get('/me/albums?fields=id,name&limit=15');
        $feedEdge = $response->getGraphEdge();

        foreach ($feedEdge as $status) {
            $dataArr = $status->asArray();
            //var_dump($dataArr);
            $album_name = $dataArr['name'];
            print $dataArr['id'] . "\r\n";
            print $album_name . "\r\n";
            if (strpos($album_name, '@') !== false) {
                $response1 = $this->fb->get("/{$dataArr['id']}?fields=id,picture,photos{id,name,images,created_time},description,type,name&limit=15");
                $feedEdge1 = $response1->getGraphNode();
                $attachments = $feedEdge1->getField('photos');
                //var_dump($attachments);
                foreach ($attachments as $attachment) {
                    $getMetaData = $attachment->asArray();
                    //var_dump($getMetaData);
                    $subattachments = $getMetaData['images'];
                    $created_time = $getMetaData['created_time'];
                    $media_id = $getMetaData['id'];
                    //var_dump($subattachments);
                    if (!empty($subattachments)) {
                        foreach ($subattachments as $subattachment) {
                            $photo_url = $subattachment['source'];
                            var_dump($photo_url);
                            $filesystem = new Filesystem();
                            $date = $created_time;
                            $filePath = $this->createImagePath($contentType = 'photos', $date, $media_id);

                            $filesystem->dumpFile(
                                $filePath,
                                $this->createImageContent($photo_url, $date, $title = '', $body = '', $album_name, $media_id)
                            );

                            $output->writeln("Created file: " . $filePath);
                            break; // we don't need different image sizes
                        }
                    }
                    //break;
                }
                //break;
            }
        }

        return self::SUCCESS;
    }

    protected function executeFeedPosts(InputInterface $input, OutputInterface $output)
    {
        // Requires the "read_stream" permission
        $response = $this->fb->get('/me/feed?fields=id,message&limit=5');
        // Page 1
        $feedEdge = $response->getGraphEdge();

        foreach ($feedEdge as $status) {
            $dataArr = $status->asArray();
            var_dump($dataArr);
            print $dataArr['id'] . "\r\n";
            // 105854321933599_109354591583572?fields=message,created_time,updated_time,attachments{url,title,subattachments,media_type},status_type,is_published
            $response1 = $this->fb->get("/{$dataArr['id']}?fields=message,created_time,updated_time,attachments{url,title,subattachments,media_type},status_type,is_published");
            // Page 1
            $feedEdge1 = $response1->getGraphNode();
            $attachments = $feedEdge1->getField('attachments');
            $created_time = $feedEdge1->getField('created_time');
            //print $created_time. "\r\n";
            //exit;
            if ($attachments != NULL) {
                $getMetaData = $attachments->asArray();
                $title = $getMetaData[0]['title']; // Photos from People’s Progress Trust's post
                $media_type = $getMetaData[0]['media_type'];
                if ($media_type == "album") {
                    //var_dump($getMetaData[0]);
                    //exit;
                    $subattachments = $getMetaData[0]['subattachments'];

                    if (!empty($subattachments)) {
                        foreach ($subattachments as $subattachment) {
                            $photo_url = $subattachment['media']['image']['src'];
                            var_dump($photo_url);
                            $filesystem = new Filesystem();
                            $media_id = $subattachment['target']['id'];
                            //$date = new \DateTime($created_time);
                            $date = $created_time;
                            $filePath = $this->createImagePath($contentType = 'photos', $date, $media_id);

                            $filesystem->dumpFile(
                                $filePath,
                                $this->createImageContent($photo_url, $date, $title = '', $body = '')
                            );

                            $output->writeln("Created file: " . $filePath);
                        }
                    } else {
                        var_dump($getMetaData);
                    }
                }
            }
            /*foreach ($feedEdge1 as $key => $status1) {
                //$dataArr1 = $status1->asArray();
                print $key."\r\n";
                //print_r((array)$status1);
                //print $dataArr1['message']."\r\n";
            }*/
        }
        return self::SUCCESS;
        /*$entries = $this->contetful->getEntries()->getItems();

        foreach ($entries as $entry) {
            $filesystem = new Filesystem();

            $contentType = strtolower($entry->getSystemProperties()->getContentType()->getName());
            $filePath = $this->createPath($contentType, $entry['date'], $entry['title']);

            $filesystem->dumpFile(
                $filePath,
                $this->createContent($entry['language'], $entry['date'], $entry['title'], $entry['contentMarkdown'])
            );

            $output->writeln("Created file: " . $filePath);
        }

        return self::SUCCESS;*/
    }

    private function createPath(string $type, \DateTime $date, string $title): string
    {
        return "source/_" . $type . "/" . $date->format('Y-m-d') . "-" . $this->normalizeTitle($title) . '.md';
    }

    private function createImagePath(string $type, \DateTime $date, string $title): string
    {
        return "source/_" . $type . "/" . $date->format('Y-m-d') . "-" . $this->normalizeTitle($title) . '.md';
    }

    private function createImageContent(string $photo_url, \DateTime $date, string $title, string $body, string $album_name = "", string $media_id = ""): string
    {
        return <<<EOL
---
createdAt: {$date->format('Y-m-d')}
title: {$title}
alt: {$title}
id: {$media_id}
photo_url: {$photo_url}
category: {$album_name}
---

{$body}
EOL;
    }

    private function createContent(string $language, \DateTime $date, string $title, string $body): string
    {
        return <<<EOL
---
createdAt: {$date->format('Y-m-d')}
title: {$title}
language: {$language}
---

{$body}
EOL;
    }

    private function normalizeTitle($title): string
    {
        $currentLocale = setlocale(LC_ALL, 0);
        setlocale(LC_ALL, 'en_US.utf8');

        $cleanTitle = strtolower($title);
        $cleanTitle = iconv('UTF-8', 'ASCII', $cleanTitle);
        $cleanTitle = preg_replace("/[^a-z0-9]+/", "-", $cleanTitle);

        setlocale(LC_ALL, $currentLocale);
        return $cleanTitle;
    }

    /*public function setContentfulClient(Client\ClientInterface $client): self
    {
        $this->contetful = $client;

        return $this;
    }*/
}
