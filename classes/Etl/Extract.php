<?php
namespace Core2\Mod\Sources\Etl;

/**
 * @property \ModProxyController   $modProxy
 * @property \ModSourcesController $modSources
 */
class Extract extends \Common {

    /**
     * @param string $url
     * @param array  $options
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function loadList(string $url, array $options = []): string {

        $responses = $this->modProxy->request('get', [$url], [
            'request' => [
                'connection'         => 10,
                'connection_timeout' => 10,
                'verify'             => false,
            ],
            'level_anonymity' => ['elite', /*'anonymous', 'non_anonymous'*/ ],
            'max_try'         => 5,
            'limit'           => 5,
            'debug'           => ! empty($options['debug_requests']) && $options['debug_requests'] ? 'print' : '',
        ]);

        $response = current($responses);

        if ($response['status'] != 'success' ||
            $response['http_code'] != '200' ||
            empty($response['content'])
        ) {
            echo $responses['error_message'] ?? '';
            return '';
        }


        return $response['content'];
    }


    /**
     * @param array $addresses
     * @param array $options
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function loadPages(array $addresses, array $options = []): array {

        $pages = [];

        if ( ! empty($addresses)) {
            $responses = $this->modProxy->request('get', $addresses, [
                'request' => [
                    'connection'         => 10,
                    'connection_timeout' => 10,
                    'verify'             => false,
                ],
                'level_anonymity' => ['elite', /*'anonymous', 'non_anonymous'*/ ],
                'max_try'         => 5,
                'limit'           => 5,
                'debug'           => ! empty($options['debug_requests']) && $options['debug_requests'] ? 'print' : '',
            ]);


            foreach ($responses as $response) {

                if ($response['status'] == 'success' &&
                    $response['http_code'] == '200' &&
                    ! empty($response['content'])
                ) {
                    $pages[] = [
                        'url'     => $response['url'],
                        'content' => $response['content'],
                    ];
                }
            }
        }

        return $pages;
    }
}