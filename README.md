# Дизайн сайта: SEO, создание сайтов, контекстная реклама

Блоки, шаблон страницы услуги, страницы блога, раздел «Вопросы по SEO» и раздел «О нас» для сайта
агентства, которое продаёт SEO-продвижение, создание сайтов и контекстную рекламу. Каждая страница
сайта собирается из блоков.

Всё работает без сборки и установки: HTML-файл открывается в любом браузере, а анимации,
аккордеоны, свайп, калькулятор, меню, фильтры, формы, содержание статьи и комментарии работают так же,
как на холсте.

## Что внутри

| Папка | Что это |
|---|---|
| [`seo-bloki/`](seo-bloki/) | 17 блоков, каждый отдельным HTML-файлом. В `index.html` все блоки собраны на одной странице, в `opisanie-blokov.txt` описаны каждый блок, цвета и шрифты. |
| [`seo-stranitsa/`](seo-stranitsa/) | Шаблон страницы услуги в одном файле `index.html`: шапка с выпадающим меню, заголовок, пять блоков, блог и подвал. Адаптирован под телефоны и планшеты. |
| [`seo-blog/`](seo-blog/) | Страницы блога: список статей `blog.html`, статья `statya.html`, страница автора `avtor.html` и блок «Свежие статьи» `svezhie-stati.html` для вставки на другие страницы. Картинки — в папке `images`. |
| [`seo-vopros/`](seo-vopros/) | Раздел «Вопросы по SEO»: страница с формой вопроса и списком ответов `vopros-seo.html`, страница ответа `otvet.html` и блок «Хотите бесплатно задать вопрос SEO-специалисту?» для вставки на другие страницы — с кнопкой (`blok-vopros.html`) и с формой (`blok-vopros-forma.html`). |
| [`o-nas/`](o-nas/) | Раздел «О нас»: контакты `kontakty.html`, о компании `o-kompanii.html`, команда `komanda.html`, отзывы `otzyvy.html`, карьера `karera.html`, публичная оферта `oferta.html`, политика cookie `politika-cookie.html`, согласие на обработку персональных данных `soglasie-pd.html` и блок «Обсудим ваш проект» `blok-cta.html`. |
| [`iskhodniki/holst-blokov/`](iskhodniki/holst-blokov/) | Исходные файлы холста с блоками (`.dc.html` и `canvas.json`). Открываются только в Claude Design, в обычном браузере не работают. |
| [`iskhodniki/holst-stranitsy/`](iskhodniki/holst-stranitsy/) | Исходные файлы холста с шаблоном страницы. Тоже только для Claude Design. |
| [`iskhodniki/holst-bloga/`](iskhodniki/holst-bloga/) | Исходные файлы холста с блогом: список статей, статья, страница автора, шапка, подвал и блок свежих статей. Только для Claude Design. |
| [`iskhodniki/holst-voprosov/`](iskhodniki/holst-voprosov/) | Исходные файлы холста «Вопросы по SEO»: страница вопросов, страница ответа, встраиваемый блок, шапка и подвал. Только для Claude Design. |
| [`iskhodniki/holst-o-nas/`](iskhodniki/holst-o-nas/) | Исходные файлы холста «О нас»: восемь страниц раздела, блок «Обсудим ваш проект», шапка и подвал. Только для Claude Design. |

## Как посмотреть

**На компьютере.** Нажмите **Code → Download ZIP**, распакуйте и откройте `seo-bloki/index.html`,
`seo-stranitsa/index.html`, `seo-blog/blog.html`, `seo-vopros/vopros-seo.html` или `o-nas/kontakty.html`.

**Онлайн по ссылке (GitHub Pages).** В репозитории откройте **Settings → Pages**, в поле
**Source** выберите **Deploy from a branch**, ветку **main** и папку **/ (root)**, нажмите **Save**.
Через пару минут страницы будут доступны по адресам:

- https://devhelpmanager.github.io/trust/seo-bloki/
- https://devhelpmanager.github.io/trust/seo-stranitsa/
- https://devhelpmanager.github.io/trust/seo-blog/blog.html — список статей
- https://devhelpmanager.github.io/trust/seo-blog/statya.html — статья
- https://devhelpmanager.github.io/trust/seo-blog/avtor.html — страница автора
- https://devhelpmanager.github.io/trust/seo-blog/svezhie-stati.html — блок «Свежие статьи»
- https://devhelpmanager.github.io/trust/seo-vopros/vopros-seo.html — вопросы по SEO
- https://devhelpmanager.github.io/trust/seo-vopros/otvet.html — страница ответа
- https://devhelpmanager.github.io/trust/o-nas/kontakty.html — контакты
- https://devhelpmanager.github.io/trust/o-nas/o-kompanii.html — о компании
- https://devhelpmanager.github.io/trust/o-nas/komanda.html — команда
- https://devhelpmanager.github.io/trust/o-nas/otzyvy.html — отзывы
- https://devhelpmanager.github.io/trust/o-nas/karera.html — карьера
- https://devhelpmanager.github.io/trust/o-nas/oferta.html — публичная оферта
- https://devhelpmanager.github.io/trust/o-nas/politika-cookie.html — политика cookie
- https://devhelpmanager.github.io/trust/o-nas/soglasie-pd.html — согласие на обработку персональных данных

## Стиль кратко

- **Фоны блоков:** фиолетовый `#5B21B6`, белый `#FFFFFF`, светло-лавандовый `#F8F5FF`.
- **Шапка и подвал страницы:** индиго `#1E1B4B`.
- **Основной цвет кнопок и ссылок:** `#6D28D9`. **Текст:** `#0E0B14`.
- **Шрифты:** Unbounded (заголовки), Onest (основной текст), JetBrains Mono (надзаголовки).
  Подключаются из Google Fonts.

Полная палитра и описание блоков: [`seo-bloki/opisanie-blokov.txt`](seo-bloki/opisanie-blokov.txt).

## Перед публикацией на сайте

- Замените заглушки в квадратных скобках (`[Бренд]`, `[Имя Фамилия]`, `[дата]` и т. п.), цены `XX XXX ₽`,
  телефон, email и юридические данные.
- В блоках 12 и 14 стоят демонстрационные скриншоты и цифры, их нужно заменить реальными.
- Отзывы в блоке 15 — заготовки под настоящие отзывы клиентов.
- Формы ничего не отправляют: обработку заявок подключает разработчик.
- В разделе «О нас» цифры, имена, фото, рейтинги, отзывы, вакансии и зарплаты — заготовки.
  Публикуйте только настоящие отзывы и с согласия клиентов.
- Публичная оферта, политика cookie и согласие на обработку персональных данных — шаблоны.
  Перед публикацией их должен проверить юрист. Для публикации отзывов и вопросов с именами нужен
  отдельный документ — согласие на распространение персональных данных.
- В формах блога, «Вопросов по SEO» и шаблона страницы услуги согласие пока дано фразой
  «Нажимая кнопку, вы соглашаетесь…». В разделе «О нас» оно уже вынесено в отдельную галочку,
  так же стоит сделать и в остальных формах.
- В блоге даты, просмотры, лайки и комментарии — примерные значения, на сайте их подставляет CMS.
  Обложки статей и фото автора — заглушки, значки соцсетей автора нужно заменить на официальные иконки.

Подробнее — в `README.md` внутри каждой папки.
