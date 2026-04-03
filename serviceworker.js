// キャッシュするリソース(必要に応じて urlsToCache に追加してください)
const CACHE_VERSION = 'v39_';
// self.registration.scope で自身のスコープを参照します
const CACHE_NAME = `${CACHE_VERSION}!${self.registration.scope}`;

const urlsToCache = [
  // '/css',
  // '/img',
  // '/favicon.ico'
];

// サービスワーカーインストール
self.addEventListener('install', function(e) {
    console.log('[ServiceWorker] Install');
    // 新しいSWをすぐに有効化する
    self.skipWaiting();
});

// サービスワーカーアクティブ
self.addEventListener('activate', function(e) {
    console.log('[ServiceWorker] Activate');
    // 古いキャッシュの削除処理などをここで行うのが一般的ですが、
    // 今はインストールを優先するため最小限にしています。
});

// サービスワーカーフェッチ（AndroidのPWAインストールにはこのイベントが必須）
self.addEventListener('fetch', function(event) {
    // 現在はキャッシュを利用せず、すべてネットワーク経由で取得する設定です。
    // インストールボタンを出すための「空のイベント」として機能します。
});



//新規開発中コード
//サービスワーカーインストール
/*
self.addEventListener('install', (event) => {
  event.waitUntil(
    // キャッシュを開く
    caches.open(CACHE_NAME)
    .then((cache) => {
      // 指定されたファイルをキャッシュに追加する
      console.log('[ServiceWorker] Install hoge');
      return cache.addAll(urlsToCache);
    })
    
  );
  //serviceworker.jsが更新されたら即有効にする（デフォルトはいったん閉じてから有効となる）
  event.waitUntil(skipWaiting());
});



//サービスワーカーアクティブ
self.addEventListener('activate', (event) => {
    console.log('[ServiceWorker] Activate hoge');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return cacheNames.filter((cacheName) => {
            // このスコープに所属していて且つCACHE_NAMEではないキャッシュを探す
            return cacheName.endsWith(`!${registration.scope}`) &&
                   cacheName !== CACHE_NAME;
            });
        }).then((cachesToDelete) => {
            return Promise.all(cachesToDelete.map((cacheName) => {
            // いらないキャッシュを削除する
            console.log('[ServiceWorker] Activate delete cache NAME:' + cacheName);
            return caches.delete(cacheName);
            }));
        })
    );
});




// サービスワーカーフェッチ

self.addEventListener('fetch', (event) => {
    //console.log('service worker fetch ... ' + event.request.url);
    event.respondWith(
        caches.match(event.request)
        .then(
            (response) => {
            // キャッシュ内に該当レスポンスがあれば、それを返す
                if (response) {
                    console.log('[ServiceWorker] fetch return cache URL:' + event.request.url + ' status:'+ response.status + ' type:' + response.type);
                    return response;
                }
                // 重要：リクエストを clone する。リクエストは Stream なので
                // 一度しか処理できない。ここではキャッシュ用、fetch 用と2回
                // 必要なので、リクエストは clone しないといけない
                let fetchRequest = event.request.clone();
    
                return fetch(fetchRequest)
                .then((response) => {
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        // キャッシュする必要のないタイプのレスポンスならそのまま返す
                        console.log('[ServiceWorker] fetch return http URL:' + event.request.url + ' status:'+ response.status + ' type:' + response.type);
                        return response;
                    }

                    // 重要：レスポンスを clone する。レスポンスは Stream で
                    // ブラウザ用とキャッシュ用の2回必要。なので clone して
                    // 2つの Stream があるようにする
                    
                    let responseToCache = response.clone();
                    let url=event.request.url;
                    console.log('[ServiceWorker] none cache:' + url);
                    if(url.indexOf('.php') == -1){
                        //phpファイルはキャッシュ対象から除く
                        console.log('[ServiceWorker] cache add:' + url);
                        caches.open(CACHE_NAME)
                        .then((cache) => {
                            cache.put(event.request, responseToCache);
                            //cache.put(event.request, response);
                        });
                    }
                    console.log('[ServiceWorker] fetch return other URL:' + event.request.url + ' status:'+ response.status + ' type:' + response.type);
                    return response;
                    
                });
            }
        )
    );
});

*/

