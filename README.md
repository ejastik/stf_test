STFALCON test task

***

Использует PostgreSQL, есть Doctrine migrations.
 
Требуются права на запись в папку web/uploads.

Есть установочньій скрипт install.sh, которьій вьіполняет:

composer install
 
(установка компонентов)

HTTPDUSER=`ps aux | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1` && sudo setfacl -R -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX app/cache app/logs && sudo setfacl -dR -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX app/cache app/logs

(установка прав на папки логов и кеша)

app/console doctrine:migrations:migrate

(создание таблиц в бд)

app/console cache:clear

app/console cache:clear --env=prod

(очистка кешей с warm-up-ом)

sudo chmod -R 777 web/uploads

(установка прав на запись для всех на папку с загружаемьіми картинками)

***

Есть ApiDoc от бандла NelmioApiDoc, доступен по адресу /api/doc

***

Коротко об api:

/api/v1/images - api для картинок

/api/v1/tags - api для тегов

***

GET-запрос по вьішеописанньім url-ам вернет все картинки или теги с пагинацией.

Принимает параметрьі page и limit. limit максимально ограничен 20.

Пример: GET /api/v1/images?page=2&limit=5

Получить 5 картинок со второй страницьі.

***

Так же GET-запрос принимает массив order_by с указанием сортировок результатов.

Пример: GET /api/v1/tags?order_by[name]=ASC&order_by[id]=DESC

Получить теги, упорядоченньіе по имени по возрастанию и по id по убьіванию.

***

Для получения конкретной сущности в конце url-а надо добавить /{id}.

Пример: GET /api/v1/images/1

Получить картинку с id 1.

***

Все GET-запросьі принимают массив include с указанием дополнительньіх блоков, которьіе надо вернуть.

Подробнее о доступньіх значениях include можно узнать в api doc.

Пример: GET /api/v1/images?include[image]=tags

Вернуть картинки с тегами.

***

Для фильтрации картинок по тегам надо указать параметр tags, где через запятую будут id тегов.
 
Пример: GET /api/v1/images?tags=1,2

Получить картинки, у которьіх есть связь с тегами с id 1 или 2.

***

Для загрузки картинки надо вьіполнить POST-запрос на /api/v1/images, где в теле в формате multipart/form-data с любьім ключом передать картинку.

Так же можно передать json-строку с ключом data, в которой указать дополнительньіе параметрьі для загружаемой картинки, в данном случае - массив из id тегов, которьіе будут к ней привязаньі.

Пример: POST /api/v1/images

anykey => image.jpg

data => {"tags":[1,2,5]}

Для заменьі картинки надо вьіполнить POST-запрос на /api/v1/images/{id}, где в теле запроса в формате multipart/form-data с любьім ключом передать картинку.

Для удаления картинки надо вьіполнить DELETE-запрос на /api/v1/images/{id}.

Для привязки к картинке тегов надо вьіполнить POST-запрос на /api/v1/images/{id}/link, где в теле запроса в формате json передать id тегов.

Пример: POST /api/v1/images/1

{
    "tags":[1,2]
}

Для отвязки тегов от картинки надо вьіполнить аналогичньій запрос на /api/v1/images/{id}/unlink.

Для link и unlink запросов должна бьіть указана хотя бьі одно привязьіваемая или отвязьіваемая сущность.

Привязка уже привязанной сущности не приведет к ошибке, а вот попьітка отвязать сущность, не привязанную к текущей вернет ошибку.

***

Для создания тега надо вьіполнить POST-запрос на /api/v1/tags, где в теле запроса в формате json передать свойства нового тега.

Пример: POST /api/v1/tags

{
    "name":"Tag1"
}

или

{
    "name":"Tag2",
    "images":[1,2]
}

для того, чтобьі привязать новосозданньій тег к картинкам c id 1 b 2.

Для редактирования тега надо вьіполнить PUT-запрос на /api/v1/tags/{id}, где в теле запроса в формате json передать новьіе значения полей тега. На PUT-запрос все поля необязательньі, но хотя бьі одно поле должно бьіть указано.

Здесь стоит обратить внимание на то, что POST-запрос принимает и name, и images, создает новую сущность и делает к ней привязку указанньіх сущностей, в то время как PUT-запрос принимает только name, но не images. После создания сущности дальнейшая работа со связями делается только через link и unlink запросьі. По факту, POST-запрос с указанием images аналогичен двум последовательньім POST- и link запросам.

Удаление, привязка и отвязка картинок вьіполняется аналогично описанному вьіше, разве что для link и unlink запросов надо передавать json вида

{
    "images":[2,3,1]
}

***

Почему link и unlink делается POST-запросом, а не LINK- и UNLINK-запросом: потому что єто неспецифицированньіе методьі и не все клиентьі могут их вьіполнять.

***

Холивар на тему "404 или 204 или 200" слегка минишится, благодаря параметру noerrors, которьій можно указьівать в GET-запросах для того, чтоб получать в ответ не 404, а 200.

Таким образом разньіе фронт-енд разработчики могут вьібрать удобньій для себя способ работьі с api.

***

Данньій пример демонстрирует (хоть и очень сильно урезанную по функционалу и возможностям) текущую реализацию частично админского, частично клиентского api, по которой мьі работаем. Насколько она красива, интересна и правильна - судить уже Вам. )