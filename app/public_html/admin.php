<?php
require_once __DIR__ . '/content.php';

function sb_admin_normalize_section(?string $section): string
{
    $section = is_string($section) ? trim($section) : '';

    return match ($section) {
        'schedule' => 'classes',
        'shop' => 'products',
        'cards' => 'visit-packs',
        'content' => 'brand',
        'site' => 'brand',
        'settings' => 'ui',
        'reservations' => 'bookings',
        'orders' => 'product-orders',
        'inquiries' => 'inquiries',
        default => $section,
    };
}

$token = shine_bright_require_admin();
if ($token !== '' && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET') {
    header('Location: ' . shine_bright_current_request_path_with_query(['token']));
    exit;
}
$content = shine_bright_load_content();
$selectedLang = isset($_GET['lang']) && in_array($_GET['lang'], ['bg', 'en'], true) ? $_GET['lang'] : 'bg';
$allowedAdminSections = ['dashboard', 'brand', 'media', 'seo', 'classes', 'events', 'products', 'clients', 'contacts', 'visit-packs', 'testimonials', 'bookings', 'inquiries', 'product-orders', 'ui'];
$selectedAdminSection = sb_admin_normalize_section(isset($_GET['section']) && is_string($_GET['section']) ? $_GET['section'] : 'dashboard');
$selectedAdminSection = in_array($selectedAdminSection, $allowedAdminSections, true) ? $selectedAdminSection : 'dashboard';
$selectedClassFilter = isset($_GET['class_filter']) && is_string($_GET['class_filter']) ? trim($_GET['class_filter']) : '';
$selectedTimeScope = isset($_GET['time_scope']) && is_string($_GET['time_scope']) ? trim($_GET['time_scope']) : '';
$selectedTimeScope = in_array($selectedTimeScope, ['today', 'tomorrow', 'week'], true) ? $selectedTimeScope : '';
$selectedSessionViewId = isset($_GET['session_view']) && is_string($_GET['session_view']) ? trim($_GET['session_view']) : '';
$selectedInquiryId = isset($_GET['inquiry']) && is_string($_GET['inquiry']) ? trim($_GET['inquiry']) : '';
$selectedVisitPage = isset($_GET['visit_page']) && is_string($_GET['visit_page']) ? trim($_GET['visit_page']) : '1';
$selectedVisitPerPage = isset($_GET['visit_per_page']) && is_string($_GET['visit_per_page']) ? trim($_GET['visit_per_page']) : '10';
$selectedItemId = isset($_GET['item_id']) && is_string($_GET['item_id']) ? trim($_GET['item_id']) : '';
$selectedMode = isset($_GET['mode']) && is_string($_GET['mode']) ? trim($_GET['mode']) : 'list';
$selectedMode = in_array($selectedMode, ['list', 'create', 'edit'], true) ? $selectedMode : 'list';
if ($selectedAdminSection === 'contacts') {
    $selectedMode = 'list';
}
$message = '';

function sb_admin_post(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function sb_admin_text(string $lang, string $text): string
{
    if ($lang !== 'bg') {
        return $text;
    }

    static $translations = [
        'Admin' => 'Админ',
        'Dashboard' => 'Табло',
        'Brand' => 'Бранд',
        'Media' => 'Медия',
        'SEO' => 'SEO',
        'Classes' => 'Класове',
        'Events' => 'Събития',
        'Products' => 'Продукти',
        'Product Orders' => 'Поръчки за продукти',
        'Students' => 'Ученици',
        'Contacts' => 'Контакти',
        'Visit Cards' => 'Карти посещения',
        'Testimonials' => 'Отзиви',
        'Class Bookings' => 'Записвания за класове',
        'Inquiries' => 'Запитвания',
        'UI Copy' => 'Текстове в интерфейса',
        'Manage brand content, classes, events, products, clients, visit cards, and class bookings.' => 'Управлявай съдържанието на бранда, класовете, събитията, продуктите, учениците, картите посещения и записванията за класове.',
        'A calmer overview of the current schedule, class bookings, and active visit-card operations.' => 'По-спокоен преглед на текущия график, записванията за класове и активните операции по картите посещения.',
        'Upcoming classes' => 'Предстоящи класове',
        'New class bookings' => 'Нови записвания',
        'Clients' => 'Ученици',
        'Active visit cards' => 'Активни карти',
        'Manage classes' => 'Управление на класове',
        'Review the current class list, open one class for editing, or add a new session.' => 'Прегледай текущия списък с класове, отвори един клас за редакция или добави нова сесия.',
        'Open classes' => 'Отвори класове',
        'Manage events' => 'Управление на събития',
        'Review special-format events, open one event for editing, or add a new event.' => 'Прегледай специалните събития, отвори едно събитие за редакция или добави ново.',
        'Open events' => 'Отвори събития',
        'Manage products' => 'Управление на продукти',
        'Review the current product list, open one product for editing, or add a new product.' => 'Прегледай текущия списък с продукти, отвори един продукт за редакция или добави нов.',
        'Open products' => 'Отвори продукти',
        'Manage students' => 'Управление на ученици',
        'Create student records and keep contact details plus notes in one place.' => 'Създай записи за ученици и пази контактите и бележките на едно място.',
        'Open students' => 'Отвори ученици',
        'Manage contacts' => 'Управление на контакти',
        'Review people known to the platform before they become students, then convert them explicitly when the relationship deepens.' => 'Прегледай хората, познати на платформата, преди да станат ученици, и ги преобразувай изрично, когато връзката се задълбочи.',
        'Open contacts' => 'Отвори контактите',
        'Manage visit cards' => 'Управление на карти посещения',
        'Issue prepaid visit cards, track remaining visits, and record usage history.' => 'Издавай предплатени карти, следи оставащите посещения и пази история на използването.',
        'Open visit cards' => 'Отвори картите',
        'Review class bookings' => 'Преглед на записванията',
        'Confirm, waitlist, or cancel guest class bookings and track attendance.' => 'Потвърждавай, поставяй в изчакване или отменяй записвания и следи присъствието.',
        'Open class bookings' => 'Отвори записванията',
        'Upcoming sessions' => 'Предстоящи сесии',
        'Next 7 days of class sessions with reservation counts so Maria can see what is coming up next.' => 'Следващите 7 дни класови сесии с брой записвания, за да вижда Мария какво предстои.',
        'No upcoming sessions scheduled yet.' => 'Все още няма планирани предстоящи сесии.',
        'No sessions today.' => 'Няма сесии днес.',
        'No sessions tomorrow.' => 'Няма сесии утре.',
        'No sessions in the next 7 days.' => 'Няма сесии в следващите 7 дни.',
        'Today' => 'Днес',
        'Tomorrow' => 'Утре',
        'Next 7 days' => 'Следващите 7 дни',
        'Guests' => 'Гости',
        'Session guests' => 'Гости за сесията',
        'No guests booked for this session yet.' => 'Все още няма гости, записани за тази сесия.',
        'Manage reservations' => 'Управлявай записванията',
        'Open session' => 'Отвори сесията',
        'Session view' => 'Изглед на сесия',
        'Reservation status' => 'Статус на резервацията',
        'Guest note' => 'Бележка от госта',
        'No guest note.' => 'Няма бележка от госта.',
        'Email status' => 'Имейл статус',
        'Initial email' => 'Първи имейл',
        'Status email' => 'Статус имейл',
        'Resend email' => 'Изпрати имейла отново',
        'Sent' => 'Изпратен',
        'Failed' => 'Неуспешен',
        'Not applicable' => 'Не се отнася',
        'Not sent yet' => 'Все още не е изпратен',
        'No email address provided.' => 'Не е въведен имейл адрес.',
        'Quick actions' => 'Бързи действия',
        'Confirm' => 'Потвърди',
        'Waitlist' => 'Изчакване',
        'Cancel' => 'Откажи',
        'Mark attended' => 'Отбележи присъствие',
        'Mark no-show' => 'Отбележи неявяване',
        'Edit note' => 'Редактирай бележка',
        'Save note' => 'Запази бележката',
        'Reservation email sent.' => 'Имейлът за резервацията е изпратен.',
        'Reservation email could not be sent.' => 'Имейлът за резервацията не можа да бъде изпратен.',
        'Quick reservation update saved.' => 'Бързата промяна по записването е запазена.',
        'Save reservation' => 'Запази записването',
        'All booking records' => 'Всички записи за резервации',
        'Use the grouped session view above for what is coming next. This list stays as the full reservation log.' => 'Използвай групирания session view по-горе за това, което предстои. Този списък остава като пълен лог на резервациите.',
        'Spot' => 'Място',
        'Organizer view' => 'Организаторски изглед',
        'Update brand' => 'Редакция на бранда',
        'Adjust founder story, homepage messaging, and contact details.' => 'Редактирай историята на Мария, началните послания и контактните детайли.',
        'Open brand' => 'Отвори бранда',
        'Manage media' => 'Управление на медия',
        'Update founder image, hero video, poster image, and hero text mode.' => 'Обнови снимката на Мария, hero видеото, постера и режима на hero текста.',
        'Open media' => 'Отвори медия',
        'Save Brand' => 'Запази бранда',
        'Save Media' => 'Запази медията',
        'Save SEO' => 'Запази SEO',
        'Interface Copy' => 'Текстове в интерфейса',
        'Save Interface Copy' => 'Запази текстовете',
        'Headline' => 'Основно послание',
        'Intro' => 'Въведение',
        'Secondary intro' => 'Второ въведение',
        'Founder name' => 'Име на Мария',
        'Founder title' => 'Титла',
        'Founder story' => 'История',
        'Email' => 'Имейл',
        'Phone' => 'Телефон',
        'Instagram URL' => 'Instagram URL',
        'Hero text mode' => 'Режим на hero текста',
        'Auto' => 'Автоматично',
        'Dark' => 'Тъмен',
        'Light' => 'Светъл',
        'Active' => 'Активна',
        'Cancelled' => 'Отказана',
        'Completed' => 'Приключена',
        'Expired' => 'Изтекла',
        'Low' => 'Малко оставащи',
        'Founder image URL' => 'URL на снимката',
        'Upload founder image' => 'Качи снимка',
        'YouTube hero video URL' => 'URL на hero видео',
        'Upload hero video' => 'Качи hero видео',
        'Hero video poster URL (optional)' => 'URL на постера (по избор)',
        'Upload hero poster' => 'Качи постер',
        'Meta title' => 'Meta заглавие',
        'Meta description' => 'Meta описание',
        'Manage classes as individual records. Open one class at a time for editing instead of changing the full schedule in one long form.' => 'Управлявай класовете като отделни записи. Отваряй по един клас за редакция, вместо да променяш целия график в една дълга форма.',
        'Add class' => 'Добави клас',
        'Title' => 'Заглавие',
        'When' => 'Кога',
        'Location' => 'Локация',
        'Start at' => 'Начало',
        'End at' => 'Край',
        'Maps URL' => 'Google Maps URL',
        'Image URL' => 'URL на изображението',
        'Price' => 'Цена',
        'Actions' => 'Действия',
        'Edit' => 'Редакция',
        'Delete' => 'Изтрий',
        'Class bookings' => 'Записвания',
        'Open class page' => 'Отвори публичната страница',
        'Class content is language-specific. Preview the same language on the public site after saving.' => 'Съдържанието на класа е по език. След запазване прегледай същия език на публичния сайт.',
        'Add Class' => 'Добави клас',
        'Edit Class' => 'Редакция на клас',
        'Calculated duration' => 'Изчислена продължителност',
        'Save' => 'Запази',
        'Save Class' => 'Запази класа',
        'Back to list' => 'Назад към списъка',
        'Manage events as individual records. Open one event at a time for editing instead of changing the whole event list in one long form.' => 'Управлявай събитията като отделни записи. Отваряй по едно събитие за редакция, вместо да променяш целия списък в една дълга форма.',
        'Add event' => 'Добави събитие',
        'Add Event' => 'Добави събитие',
        'Edit Event' => 'Редакция на събитие',
        'Save Event' => 'Запази събитието',
        'Manage products as individual records. Open one product at a time for editing instead of working through the full product list in one long form.' => 'Управлявай продуктите като отделни записи. Отваряй по един продукт за редакция, вместо да работиш върху целия списък в една дълга форма.',
        'Add product' => 'Добави продукт',
        'Product' => 'Продукт',
        'Class' => 'Клас',
        'Event' => 'Събитие',
        'Inquiry' => 'Запитване',
        'Category' => 'Категория',
        'Detail' => 'Детайл',
        'Short description' => 'Кратко описание',
        'Image focus X' => 'Позиция на изображението X',
        'Image focus Y' => 'Позиция на изображението Y',
        'Image zoom' => 'Увеличение на изображението',
        'Add Product' => 'Добави продукт',
        'Edit Product' => 'Редакция на продукт',
        'Upload product image' => 'Качи снимка на продукта',
        'Product image framing' => 'Кадриране на изображението',
        'Adjust how the product image sits in its card and detail view.' => 'Настрой как стои изображението на продукта в картата и детайлната страница.',
        'Save Product' => 'Запази продукта',
        'Keep one record per person so visit cards and future attendance notes stay attached to a stable student record.' => 'Поддържай по един запис за човек, за да бъдат картите и бъдещите бележки за посещения към стабилен запис на ученик.',
        'Add student' => 'Добави ученик',
        'Student' => 'Ученик',
        'Visit cards' => 'Карти',
        'Add Student' => 'Добави ученик',
        'Edit Student' => 'Редакция на ученик',
        'Name' => 'Име',
        'Notes' => 'Бележки',
        'Save Student' => 'Запази ученика',
        'Choose student' => 'Избери ученик',
        'Access status' => 'Статус на достъпа',
        'Other contacts' => 'Други контакти',
        'These people exist in reservations or inquiries, but do not yet have a stable student record. They are shown here for visibility only so student CRUD stays safe.' => 'Тези хора съществуват в резервации или запитвания, но все още нямат стабилен запис на ученик. Показват се тук за видимост, без да се засяга безопасността на student CRUD.',
        'Contacts are people known to the platform through reservations or inquiries, but who are not yet students. Convert them explicitly when Maria wants to bring them into the managed student flow.' => 'Контактите са хора, познати на платформата чрез резервации или запитвания, но които още не са ученици. Преобразувай ги изрично, когато Мария иска да ги вкара в управлявания student flow.',
        'No contacts yet.' => 'Все още няма контакти.',
        'Source' => 'Източник',
        'Lifecycle' => 'Етап',
        'Reservation' => 'Резервация',
        'Inquiry' => 'Запитване',
        'Lead' => 'Потенциален клиент',
        'Reserved' => 'Запазил място',
        'Attended' => 'Посетил',
        'Convert to student' => 'Преобразувай в ученик',
        'This contact already exists as a student.' => 'Този контакт вече съществува като ученик.',
        'This contact needs a valid email before becoming a student.' => 'Този контакт има нужда от валиден имейл, преди да стане ученик.',
        'Contact converted to student.' => 'Контактът е преобразуван в ученик.',
        'Invited' => 'Поканен',
        'Disabled' => 'Деактивиран',
        'Send activation email' => 'Изпрати имейл за активиране',
        'Activation email sent.' => 'Имейлът за активиране е изпратен.',
        'Activation email could not be sent.' => 'Имейлът за активиране не можа да бъде изпратен.',
        'Enter a valid email address.' => 'Въведете валиден имейл адрес.',
        'A student with this email already exists.' => 'Ученик с този имейл вече съществува.',
        'Issue prepaid visit cards, watch remaining visits, and quickly spot low, completed, or expired cards.' => 'Издавай предплатени карти, следи оставащите посещения и бързо виждай изчерпани, изтичащи или приключени карти.',
        'Add visit card' => 'Добави карта',
        'Valid for' => 'Валидна за',
        'Card' => 'Карта',
        'Visits' => 'Посещения',
        'Expiry' => 'Валидност',
        'Status' => 'Статус',
        'Add Visit Card' => 'Добави карта',
        'Edit Visit Card' => 'Редакция на карта',
        'All classes' => 'Всички класове',
        'Valid for class' => 'Валидна за клас',
        'Valid for classes' => 'Валидна за класове',
        'Total visits' => 'Общо посещения',
        'Used visits' => 'Използвани посещения',
        'Starts on' => 'Начална дата',
        'Expires on' => 'Крайна дата',
        'Save Visit Card' => 'Запази картата',
        'Use 1 Visit' => 'Използвай 1 посещение',
        'Use 1 visit' => 'Използвай 1 посещение',
        'Record one attendance from this visit card. Class and note are optional in v1.' => 'Запиши едно посещение от тази карта. Класът и бележката са по избор във v1.',
        'Used on' => 'Използвано на',
        'Class (optional)' => 'Клас (по избор)',
        'Manual / no class linked' => 'Ръчно / без свързан клас',
        'Attendance' => 'Присъствие',
        'Note' => 'Бележка',
        'Optional attendance note' => 'Бележка за посещението (по избор)',
        'Usage History' => 'История на използването',
        'No visits have been recorded yet.' => 'Все още няма записани посещения.',
        'Choose one or more classes, or leave everything unchecked for all classes.' => 'Избери един или повече класове, или остави всичко празно за всички класове.',
        'Manage testimonials as individual records. Open one testimonial at a time for editing.' => 'Управлявай отзивите като отделни записи. Отваряй по един отзив за редакция.',
        'Add testimonial' => 'Добави отзив',
        'Quote' => 'Цитат',
        'Add Testimonial' => 'Добави отзив',
        'Edit Testimonial' => 'Редакция на отзив',
        'Save Testimonial' => 'Запази отзива',
        'Class Sessions' => 'Сесии на класовете',
        'Use this as the main organizer view. Each class shows its booking totals and quick links to edit the class or review its class bookings.' => 'Използвай това като основен организационен изглед. Всеки клас показва общите записвания и бързи връзки за редакция или преглед на записванията.',
        'Total' => 'Общо',
        'New' => 'Нови',
        'Confirmed' => 'Потвърдени',
        'Waitlisted' => 'Изчакване',
        'Attended' => 'Присъствали',
        'Pending' => 'Чака',
        'No-show' => 'Неявил се',
        'Edit class' => 'Редакция на клас',
        'View class bookings' => 'Виж записванията',
        'Showing class bookings only for' => 'Показват се записвания само за',
        'Show all class bookings' => 'Покажи всички записвания',
        'No class bookings yet.' => 'Все още няма записвания за класове.',
        'Organizer note' => 'Бележка на организатора',
        'Guest note:' => 'Бележка от госта:',
        'Save Reservation' => 'Запази записването',
        'When' => 'Кога',
        'Type' => 'Тип',
        'Contact' => 'Контакт',
        'Item' => 'Елемент',
        'View' => 'Виж',
        'Open inquiry' => 'Отвори запитването',
        'Delete inquiry' => 'Изтрий запитването',
        'Inquiry deleted.' => 'Запитването е изтрито.',
        'All' => 'Всички',
        'Page' => 'Страница',
        'Previous' => 'Назад',
        'Next' => 'Напред',
        'Show' => 'Покажи',
        'Showing visit cards' => 'Показани карти посещения',
        'of total visit cards' => 'от общо карти',
        'Inquiry details' => 'Детайли за запитването',
        'Back to inquiries' => 'Назад към запитванията',
        'Inquiry not found.' => 'Запитването не е намерено.',
        'Source path' => 'Източник',
        'Item ID' => 'ID на елемента',
        'Session ID' => 'ID на сесията',
        'Schedule ID' => 'ID на графика',
        'Quantity' => 'Количество',
        'Message' => 'Съобщение',
        'No message provided.' => 'Няма въведено съобщение.',
        'IP hash' => 'IP хеш',
        'User agent' => 'User agent',
        'No inquiries yet.' => 'Все още няма запитвания.',
        'No product orders yet.' => 'Все още няма продуктови поръчки.',
        'Order status' => 'Статус на поръчката',
        'New order' => 'Нова поръчка',
        'Confirmed order' => 'Потвърдена',
        'Shipped order' => 'Изпратена',
        'Order status updated.' => 'Статусът на поръчката е обновен.',
        'Manage product orders separately from general inquiries.' => 'Управлявай продуктовите поръчки отделно от общите запитвания.',
        'Apply class filter' => 'Филтър по клас',
        'Open filtered bookings' => 'Отвори филтрираните записвания',
        'Access' => 'Достъп',
        'Admin is protected by the same token pattern as the rest of this repo. Open it with:' => 'Админът е защитен със същия token модел като останалата част от проекта. Отвори го с:',
        'Current public site:' => 'Текущ публичен сайт:',
        'Preview current language' => 'Преглед на текущия език',
        'Log out' => 'Изход',
        'Admin login' => 'Вход за админ',
        'QR Check-in' => 'QR check-in',
        'Open QR check-in' => 'Отвори QR check-in',
        'Scan student QR codes and confirm one visit without opening the full visit-card editor.' => 'Сканирай QR кодове на ученици и потвърждавай по едно посещение без да отваряш цялата редакция на картата.',
        'Admin access now uses a secure session. Existing token links can still bootstrap a session while the new password login is being rolled out.' => 'Достъпът до админа вече използва защитена сесия. Съществуващите token връзки все още могат да стартират сесия, докато новият вход с парола бъде въведен напълно.',
        'Add' => 'Добавяне',
        'Open current media' => 'Отвори текущия файл',
        'Brand content saved.' => 'Съдържанието на бранда е запазено.',
        'SEO content saved.' => 'SEO съдържанието е запазено.',
        'Media settings saved.' => 'Медия настройките са запазени.',
        'Interface copy saved.' => 'Текстовете в интерфейса са запазени.',
        'Another client already uses this id.' => 'Друг ученик вече използва този идентификатор.',
        'Client saved.' => 'Ученикът е запазен.',
        'Clients with visit cards cannot be deleted.' => 'Ученици с карти посещения не могат да бъдат изтрити.',
        'Students with visit history cannot be deleted.' => 'Ученици с история на посещения не могат да бъдат изтрити.',
        'Client deleted.' => 'Ученикът е изтрит.',
        'Client could not be deleted.' => 'Ученикът не можа да бъде изтрит.',
        'Choose a valid client for this visit card.' => 'Избери валиден ученик за тази карта.',
        'Another visit card already uses this id.' => 'Друга карта вече използва този идентификатор.',
        'Visit card saved.' => 'Картата е запазена.',
        'Visit card created.' => 'Създадена е нова карта.',
        'Visit card updated.' => 'Картата е обновена.',
        'Created new visit card:' => 'Създадена е нова карта:',
        'Updated existing visit card:' => 'Обновена е съществуваща карта:',
        'Record ID' => 'ID на записа',
        'Created at' => 'Създадена на',
        'Updated at' => 'Обновена на',
        'Visit cards with usage history cannot be deleted.' => 'Карти с история на използване не могат да бъдат изтрити.',
        'Visit card deleted.' => 'Картата е изтрита.',
        'One visit recorded.' => 'Едно посещение е записано.',
        'Another class already uses this id.' => 'Друг клас вече използва този идентификатор.',
        'Another event already uses this id.' => 'Друго събитие вече използва този идентификатор.',
        'Another product already uses this id.' => 'Друг продукт вече използва този идентификатор.',
        'Another testimonial already uses this id.' => 'Друг отзив вече използва този идентификатор.',
        'Classes item saved.' => 'Класът е запазен.',
        'Events item saved.' => 'Събитието е запазено.',
        'Products item saved.' => 'Продуктът е запазен.',
        'Testimonials item saved.' => 'Отзивът е запазен.',
        'Classes item added.' => 'Класът е добавен.',
        'Events item added.' => 'Събитието е добавено.',
        'Products item added.' => 'Продуктът е добавен.',
        'Testimonials item added.' => 'Отзивът е добавен.',
        'Classes item deleted.' => 'Класът е изтрит.',
        'Events item deleted.' => 'Събитието е изтрито.',
        'Products item deleted.' => 'Продуктът е изтрит.',
        'Testimonials item deleted.' => 'Отзивът е изтрит.',
        'Reservation updated.' => 'Записването е обновено.',
        'Schedules' => 'Графици',
        'Add schedule' => 'Добави график',
        'Weekday' => 'Ден',
        'Start time' => 'Начален час',
        'End time' => 'Краен час',
        'Remove schedule' => 'Премахни график',
        'Schedule summary' => 'График',
        'Primary location' => 'Основна локация',
        'No schedules yet.' => 'Все още няма графици.',
    ];

    return $translations[$text] ?? $text;
}

function sb_admin_field_label(string $lang, string $field): string
{
    $labels = [
        'start_at' => 'Start at',
        'end_at' => 'End at',
        'maps_url' => 'Maps URL',
        'price_eur' => 'Price',
        'image_url' => 'Image URL',
        'start_time' => 'Start time',
        'end_time' => 'End time',
    ];

    return sb_admin_text($lang, $labels[$field] ?? ucfirst(str_replace('_', ' ', $field)));
}

function sb_admin_weekday_options(string $lang, string $selected = ''): string
{
    $options = '';
    foreach (shine_bright_weekday_labels($lang) as $value => $label) {
        $selectedAttr = $value === $selected ? ' selected' : '';
        $options .= '<option value="' . htmlspecialchars($value) . '"' . $selectedAttr . '>' . htmlspecialchars($label) . '</option>';
    }
    return $options;
}

function sb_admin_class_schedule_summary_text(array $item, string $lang): string
{
    $summary = shine_bright_class_schedule_summary($item, $lang, 3);
    return implode(' · ', $summary);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sb_admin_post('action');
    $selectedLang = sb_admin_post('lang') !== '' ? sb_admin_post('lang') : $selectedLang;
    $postedSection = sb_admin_post('admin_section') !== '' ? sb_admin_normalize_section(sb_admin_post('admin_section')) : '';
    $selectedAdminSection = $postedSection !== '' && in_array($postedSection, $allowedAdminSections, true)
        ? $postedSection
        : $selectedAdminSection;
    $selectedClassFilter = sb_admin_post('class_filter') !== '' ? sb_admin_post('class_filter') : $selectedClassFilter;
    $selectedTimeScope = sb_admin_post('time_scope') !== '' ? sb_admin_post('time_scope') : $selectedTimeScope;
    $selectedTimeScope = in_array($selectedTimeScope, ['today', 'tomorrow', 'week'], true) ? $selectedTimeScope : '';
    $selectedSessionViewId = sb_admin_post('session_view') !== '' ? sb_admin_post('session_view') : $selectedSessionViewId;
    $selectedVisitPage = sb_admin_post('visit_page') !== '' ? sb_admin_post('visit_page') : $selectedVisitPage;
    $selectedVisitPerPage = sb_admin_post('visit_per_page') !== '' ? sb_admin_post('visit_per_page') : $selectedVisitPerPage;
    $selectedItemId = sb_admin_post('selected_item_id') !== '' ? sb_admin_post('selected_item_id') : $selectedItemId;
    $selectedMode = sb_admin_post('selected_mode') !== '' ? sb_admin_post('selected_mode') : $selectedMode;
    $selectedMode = in_array($selectedMode, ['list', 'create', 'edit'], true) ? $selectedMode : 'list';

    if (!in_array($selectedLang, ['bg', 'en'], true)) {
        $selectedLang = 'bg';
    }

    if ($action === 'save_brand') {
        $content[$selectedLang]['brand']['headline'] = sb_admin_post('brand_headline');
        $content[$selectedLang]['brand']['intro'] = sb_admin_post('brand_intro');
        $content[$selectedLang]['brand']['subintro'] = sb_admin_post('brand_subintro');
        $content[$selectedLang]['brand']['founder_name'] = sb_admin_post('founder_name');
        $content[$selectedLang]['brand']['founder_title'] = sb_admin_post('founder_title');
        $content[$selectedLang]['brand']['founder_story'] = sb_admin_post('founder_story');
        $content[$selectedLang]['brand']['contact_email'] = sb_admin_post('contact_email');
        $content[$selectedLang]['brand']['contact_phone'] = sb_admin_post('contact_phone');
        $content[$selectedLang]['brand']['instagram'] = sb_admin_post('instagram');

        shine_bright_save_content($content);
        $message = sb_admin_text($selectedLang, 'Brand content saved.');
    }

    if ($action === 'save_seo') {
        $content[$selectedLang]['meta']['title'] = sb_admin_post('meta_title');
        $content[$selectedLang]['meta']['description'] = sb_admin_post('meta_description');

        shine_bright_save_content($content);
        $message = sb_admin_text($selectedLang, 'SEO content saved.');
    }

    if ($action === 'save_media') {
        $heroTextMode = sb_admin_post('hero_text_mode');
        $content[$selectedLang]['brand']['hero_text_mode'] = in_array($heroTextMode, ['auto', 'dark', 'light'], true) ? $heroTextMode : 'auto';
        $content[$selectedLang]['brand']['founder_image_url'] = sb_admin_post('founder_image_url');
        $content[$selectedLang]['brand']['hero_video_url'] = sb_admin_post('hero_video_url');
        $content[$selectedLang]['brand']['hero_video_poster_url'] = sb_admin_post('hero_video_poster_url');

        try {
            $founderUpload = shine_bright_upload_media('founder_image_file', [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ], 'founder');
            if ($founderUpload) {
                $content[$selectedLang]['brand']['founder_image_url'] = $founderUpload;
            }

            $heroVideoUpload = shine_bright_upload_media('hero_video_file', [
                'video/mp4' => 'mp4',
                'video/webm' => 'webm',
                'video/quicktime' => 'mov',
            ], 'hero-video');
            if ($heroVideoUpload) {
                $content[$selectedLang]['brand']['hero_video_url'] = $heroVideoUpload;
            }

            $posterUpload = shine_bright_upload_media('hero_video_poster_file', [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ], 'hero-poster');
            if ($posterUpload) {
                $content[$selectedLang]['brand']['hero_video_poster_url'] = $posterUpload;
            }
        } catch (RuntimeException $e) {
            $message = $e->getMessage();
        }

        shine_bright_save_content($content);
        $message = $message !== '' ? $message : sb_admin_text($selectedLang, 'Media settings saved.');
    }

    if ($action === 'save_ui') {
        $editableUiFields = [
            'contact_eyebrow',
            'contact_heading',
            'contact_body',
            'contact_inquiry_cta',
            'phone_cta',
            'inquiry_default_title',
            'inquiry_product_prefix',
            'inquiry_general_prefix',
            'general_dialog_title',
            'reserve_dialog_title',
            'event_dialog_title',
            'product_dialog_title',
            'reserve_context',
            'event_context',
            'product_context',
            'general_context',
            'type_label_class',
            'type_label_event',
            'type_label_product',
            'type_label_general',
            'reserve_submit',
            'event_submit',
            'product_submit',
            'send_inquiry',
            'cancel',
            'close_dialog',
            'field_message_placeholder',
            'field_message_placeholder_class',
            'field_message_placeholder_event',
            'field_message_placeholder_product',
            'success',
            'success_class',
            'success_event',
            'success_product',
            'error_invalid_email',
            'error_invalid_phone',
        ];

        foreach ($editableUiFields as $field) {
            $content[$selectedLang]['ui'][$field] = sb_admin_post('ui_' . $field);
        }

        shine_bright_save_content($content);
        $message = sb_admin_text($selectedLang, 'Interface copy saved.');
    }

    if ($action === 'save_client') {
        $clients = shine_bright_load_clients();
        $tokens = shine_bright_load_student_activation_tokens();
        $originalClientId = sb_admin_post('original_client_id');
        $email = strtolower(sb_admin_post('client_email'));
        $payload = [
            'id' => sb_admin_post('client_id') !== '' ? sb_admin_post('client_id') : $originalClientId,
            'name' => sb_admin_post('client_name'),
            'phone' => sb_admin_post('client_phone'),
            'email' => $email,
            'notes' => sb_admin_post('client_notes'),
        ];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = sb_admin_text($selectedLang, 'Enter a valid email address.');
        } else {
            $existingByEmail = shine_bright_find_student_by_email($clients, $email);
            if ($existingByEmail && (string) ($existingByEmail['id'] ?? '') !== $originalClientId) {
                $selectedAdminSection = 'clients';
                $selectedItemId = (string) ($existingByEmail['id'] ?? '');
                $selectedMode = 'edit';
                $message = sb_admin_text($selectedLang, 'A student with this email already exists.');
            } else {
                $normalized = shine_bright_normalize_client($payload);
                $existingTarget = shine_bright_find_record_by_id($clients, $normalized['id']);
                if ($existingTarget && $originalClientId !== $normalized['id']) {
                    $message = sb_admin_text($selectedLang, 'Another client already uses this id.');
                } else {
                    if ($originalClientId !== '' && $originalClientId !== $normalized['id']) {
                        shine_bright_delete_client($clients, $originalClientId);
                    }

                    if ($originalClientId === '') {
                        $payload['account_status'] = 'invited';
                    } else {
                        $existingStudent = shine_bright_find_record_by_id($clients, $originalClientId);
                        if ($existingStudent) {
                            $payload['account_status'] = (string) ($existingStudent['account_status'] ?? 'invited');
                            $payload['password_hash'] = (string) ($existingStudent['password_hash'] ?? '');
                            $payload['last_login_at'] = (string) ($existingStudent['last_login_at'] ?? '');
                        }
                    }

                    $savedClient = shine_bright_upsert_client($clients, $payload);
                    shine_bright_save_clients($clients);
                    $selectedAdminSection = 'clients';
                    $selectedItemId = (string) ($savedClient['id'] ?? '');
                    $selectedMode = 'edit';
                    $message = sb_admin_text($selectedLang, 'Client saved.');

                    if ($originalClientId === '') {
                        $sent = shine_bright_send_student_activation_email($clients, $tokens, (string) ($savedClient['id'] ?? ''), $selectedLang);
                        if ($sent) {
                            shine_bright_save_clients($clients);
                            $message .= ' ' . sb_admin_text($selectedLang, 'Activation email sent.');
                        } else {
                            $message .= ' ' . sb_admin_text($selectedLang, 'Activation email could not be sent.');
                        }
                    }
                }
            }
        }
    }

    if ($action === 'delete_client') {
        $clientId = sb_admin_post('client_id');
        $clients = shine_bright_load_clients();
        $packs = shine_bright_load_visit_packs();
        $usage = shine_bright_load_visit_usage();
        $tokens = shine_bright_load_student_activation_tokens();
        $qrCodes = shine_bright_load_qr_checkin_codes();

        if (shine_bright_client_pack_count($packs, $clientId) > 0) {
            $message = sb_admin_text($selectedLang, 'Clients with visit cards cannot be deleted.');
        } elseif (shine_bright_client_usage_count($usage, $clientId) > 0) {
            $message = sb_admin_text($selectedLang, 'Students with visit history cannot be deleted.');
        } elseif (shine_bright_delete_client_runtime($clients, $tokens, $qrCodes, $clientId)) {
            shine_bright_save_clients($clients);
            shine_bright_save_student_activation_tokens($tokens);
            shine_bright_save_qr_checkin_codes($qrCodes);
            if ($selectedAdminSection === 'clients' && $selectedItemId === $clientId) {
                $selectedItemId = '';
                $selectedMode = 'list';
            }
            $message = sb_admin_text($selectedLang, 'Client deleted.');
        } else {
            $message = sb_admin_text($selectedLang, 'Client could not be deleted.');
        }
    }

    if ($action === 'convert_contact') {
        $leadId = sb_admin_post('lead_id');
        $clients = shine_bright_load_clients();
        $contactLeadsForAction = shine_bright_contact_lead_index($clients, shine_bright_load_reservations(), shine_bright_read_inquiries(50));
        $lead = shine_bright_find_record_by_id($contactLeadsForAction, $leadId);

        if (!$lead) {
            $message = sb_admin_text($selectedLang, 'Client could not be saved.');
        } else {
            $email = strtolower(trim((string) ($lead['email'] ?? '')));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = sb_admin_text($selectedLang, 'This contact needs a valid email before becoming a student.');
            } else {
                $existingByEmail = shine_bright_find_student_by_email($clients, $email);
                if ($existingByEmail) {
                    $selectedAdminSection = 'clients';
                    $selectedItemId = (string) ($existingByEmail['id'] ?? '');
                    $selectedMode = 'edit';
                    $message = sb_admin_text($selectedLang, 'This contact already exists as a student.');
                } else {
                    $payload = [
                        'name' => (string) ($lead['name'] ?? ''),
                        'phone' => (string) ($lead['phone'] ?? ''),
                        'email' => $email,
                        'notes' => 'Converted from ' . (string) ($lead['source'] ?? 'contact') . '.',
                        'account_status' => 'invited',
                    ];
                    $savedClient = shine_bright_upsert_client($clients, $payload);
                    shine_bright_save_clients($clients);
                    $selectedAdminSection = 'clients';
                    $selectedItemId = (string) ($savedClient['id'] ?? '');
                    $selectedMode = 'edit';
                    $message = sb_admin_text($selectedLang, 'Contact converted to student.');
                }
            }
        }
    }

    if ($action === 'save_visit_pack') {
        $packs = shine_bright_load_visit_packs();
        $clients = shine_bright_load_clients();
        $originalPackId = sb_admin_post('original_pack_id');
        $payload = [
            'id' => sb_admin_post('pack_id') !== '' ? sb_admin_post('pack_id') : $originalPackId,
            'client_id' => sb_admin_post('pack_client_id'),
            'applies_to_class_ids' => isset($_POST['pack_applies_to_class_ids']) && is_array($_POST['pack_applies_to_class_ids'])
                ? array_values(array_filter(array_map(static fn ($value): string => trim((string) $value), $_POST['pack_applies_to_class_ids']), static fn (string $value): bool => $value !== ''))
                : [],
            'title' => sb_admin_post('pack_title'),
            'total_visits' => sb_admin_post('pack_total_visits'),
            'used_visits' => sb_admin_post('pack_used_visits'),
            'starts_on' => sb_admin_post('pack_starts_on'),
            'expires_on' => sb_admin_post('pack_expires_on'),
            'status' => sb_admin_post('pack_status'),
            'notes' => sb_admin_post('pack_notes'),
        ];

        if (!shine_bright_find_record_by_id($clients, (string) $payload['client_id'])) {
            $message = sb_admin_text($selectedLang, 'Choose a valid client for this visit card.');
        } else {
            $normalized = shine_bright_normalize_visit_pack($payload);
            $existingTarget = shine_bright_find_record_by_id($packs, $normalized['id']);
            if ($existingTarget && $originalPackId !== $normalized['id']) {
                if ($originalPackId === '') {
                    $attempt = 0;
                    do {
                        $attempt++;
                        $payload['id'] = $normalized['id'] . '-' . gmdate('His') . '-' . bin2hex(random_bytes(2));
                        $normalized = shine_bright_normalize_visit_pack($payload);
                    } while (shine_bright_find_record_by_id($packs, $normalized['id']) && $attempt < 5);
                } else {
                    $message = sb_admin_text($selectedLang, 'Another visit card already uses this id.');
                }
            }
            if ($message === '') {
                $wasUpdate = $originalPackId !== '' || $existingTarget !== null;
                if ($originalPackId !== '' && $originalPackId !== $normalized['id']) {
                    shine_bright_delete_visit_pack($packs, $originalPackId);
                }

                $savedPack = shine_bright_upsert_visit_pack($packs, $payload);
                shine_bright_save_visit_packs($packs);
                $selectedAdminSection = 'visit-packs';
                $selectedItemId = (string) ($savedPack['id'] ?? '');
                $selectedMode = 'edit';
                $message = sb_admin_text(
                    $selectedLang,
                    $wasUpdate ? 'Updated existing visit card:' : 'Created new visit card:'
                ) . ' ' . (string) ($savedPack['id'] ?? '');
            }
        }
    }

    if ($action === 'delete_visit_pack') {
        $packId = sb_admin_post('pack_id');
        $packs = shine_bright_load_visit_packs();
        $usage = shine_bright_load_visit_usage();

        if (!shine_bright_visit_pack_delete_allowed($usage, $packId)) {
            $message = sb_admin_text($selectedLang, 'Visit cards with usage history cannot be deleted.');
        } elseif (shine_bright_delete_visit_pack($packs, $packId)) {
            shine_bright_save_visit_packs($packs);
            if ($selectedAdminSection === 'visit-packs' && $selectedItemId === $packId) {
                $selectedItemId = '';
                $selectedMode = 'list';
            }
            $message = sb_admin_text($selectedLang, 'Visit card deleted.');
        }
    }

    if ($action === 'use_visit_pack') {
        $packId = sb_admin_post('pack_id');
        $packs = shine_bright_load_visit_packs();

        try {
            $updatedPack = shine_bright_consume_visit_pack($packs, $packId, [
                'class_id' => sb_admin_post('usage_class_id'),
                'used_on' => sb_admin_post('usage_used_on'),
                'source' => 'admin',
                'note' => sb_admin_post('usage_note'),
            ]);
            shine_bright_save_visit_packs($packs);
            $selectedAdminSection = 'visit-packs';
            $selectedItemId = (string) ($updatedPack['id'] ?? '');
            $selectedMode = 'edit';
            $message = sb_admin_text($selectedLang, 'One visit recorded.');
        } catch (RuntimeException $e) {
            $message = $e->getMessage();
        }
    }

    if ($action === 'send_student_invitation') {
        $studentId = sb_admin_post('student_id');
        $clients = shine_bright_load_clients();
        $tokens = shine_bright_load_student_activation_tokens();
        $sent = shine_bright_send_student_activation_email($clients, $tokens, $studentId, $selectedLang);
        $message = $sent
            ? sb_admin_text($selectedLang, 'Activation email sent.')
            : sb_admin_text($selectedLang, 'Activation email could not be sent.');
        $selectedAdminSection = 'clients';
        $selectedItemId = $studentId;
        $selectedMode = 'edit';
    }

    if ($action === 'delete_inquiry') {
        $inquiryId = sb_admin_post('inquiry_id');
        $inquiryMeta = shine_bright_load_inquiry_meta();

        if ($inquiryId !== '') {
            $meta = shine_bright_normalize_inquiry_meta($inquiryMeta[$inquiryId] ?? []);
            $meta['deleted_at'] = gmdate('c');
            $meta['updated_at'] = gmdate('c');
            $inquiryMeta[$inquiryId] = $meta;
            shine_bright_save_inquiry_meta($inquiryMeta);
            if ($selectedInquiryId === $inquiryId) {
                $selectedInquiryId = '';
            }
            $message = sb_admin_text($selectedLang, 'Inquiry deleted.');
        }
    }

    if ($action === 'update_product_order_status') {
        $inquiryId = sb_admin_post('inquiry_id');
        $orderStatus = sb_admin_post('order_status');
        $inquiryMeta = shine_bright_load_inquiry_meta();

        if ($inquiryId !== '' && in_array($orderStatus, ['new', 'confirmed', 'cancelled', 'shipped'], true)) {
            $meta = shine_bright_normalize_inquiry_meta($inquiryMeta[$inquiryId] ?? []);
            $meta['order_status'] = $orderStatus;
            $meta['updated_at'] = gmdate('c');
            $inquiryMeta[$inquiryId] = $meta;
            shine_bright_save_inquiry_meta($inquiryMeta);
            $message = sb_admin_text($selectedLang, 'Order status updated.');
        }
    }

    if ($action === 'save_item') {
        $section = sb_admin_post('section');
        $originalItemId = sb_admin_post('original_item_id');
        $allowed = shine_bright_content_sections();

        if (in_array($section, $allowed, true)) {
            $fields = array_keys(shine_bright_content_item_templates()[$section] ?? []);
            $payload = [];

            foreach ($fields as $field) {
                if ($field === 'id') {
                    continue;
                }
                if ($section === 'classes' && $field === 'schedules') {
                    continue;
                }
                $payload[$field] = sb_admin_post('field_' . $field);
            }

            if ($section === 'classes') {
                $scheduleWeekdays = $_POST['schedule_weekday'] ?? [];
                $scheduleStarts = $_POST['schedule_start_time'] ?? [];
                $scheduleEnds = $_POST['schedule_end_time'] ?? [];
                $scheduleLocations = $_POST['schedule_location'] ?? [];
                $scheduleMaps = $_POST['schedule_maps_url'] ?? [];
                $scheduleIds = $_POST['schedule_id'] ?? [];
                $schedules = [];
                $maxSchedules = max(
                    is_array($scheduleWeekdays) ? count($scheduleWeekdays) : 0,
                    is_array($scheduleStarts) ? count($scheduleStarts) : 0,
                    is_array($scheduleEnds) ? count($scheduleEnds) : 0,
                    is_array($scheduleLocations) ? count($scheduleLocations) : 0,
                    is_array($scheduleMaps) ? count($scheduleMaps) : 0,
                    is_array($scheduleIds) ? count($scheduleIds) : 0
                );

                for ($i = 0; $i < $maxSchedules; $i++) {
                    $schedule = shine_bright_normalize_class_schedule([
                        'id' => is_array($scheduleIds) ? (string) ($scheduleIds[$i] ?? '') : '',
                        'weekday' => is_array($scheduleWeekdays) ? (string) ($scheduleWeekdays[$i] ?? '') : '',
                        'start_time' => is_array($scheduleStarts) ? (string) ($scheduleStarts[$i] ?? '') : '',
                        'end_time' => is_array($scheduleEnds) ? (string) ($scheduleEnds[$i] ?? '') : '',
                        'location' => is_array($scheduleLocations) ? (string) ($scheduleLocations[$i] ?? '') : '',
                        'maps_url' => is_array($scheduleMaps) ? (string) ($scheduleMaps[$i] ?? '') : '',
                    ], $i);

                    if (
                        $schedule['weekday'] === ''
                        && $schedule['start_time'] === ''
                        && $schedule['end_time'] === ''
                        && $schedule['location'] === ''
                        && $schedule['maps_url'] === ''
                    ) {
                        continue;
                    }

                    $schedules[] = $schedule;
                }

                $payload['schedules'] = $schedules;
            }

            $itemId = sb_admin_post('item_id');
            $payload['id'] = $itemId !== '' ? $itemId : $originalItemId;

            if ($section === 'products') {
                try {
                    $productUpload = shine_bright_upload_media('product_image_file', [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                    ], 'product');
                    if ($productUpload) {
                        $payload['image_url'] = $productUpload;
                    }
                } catch (RuntimeException $e) {
                    $message = $e->getMessage();
                }
            }

            if ($section === 'classes') {
                try {
                    $classUpload = shine_bright_upload_media('class_image_file', [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                    ], 'class');
                    if ($classUpload) {
                        $payload['image_url'] = $classUpload;
                    }
                } catch (RuntimeException $e) {
                    $message = $e->getMessage();
                }
            }

            $normalized = shine_bright_normalize_content_item($section, $payload);
            $existingTarget = shine_bright_find_content_item($content, $selectedLang, $section, $normalized['id']);
            if ($existingTarget && $originalItemId !== $normalized['id']) {
                $collisionLabel = match ($section) {
                    'classes' => 'Another class already uses this id.',
                    'events' => 'Another event already uses this id.',
                    'products' => 'Another product already uses this id.',
                    'testimonials' => 'Another testimonial already uses this id.',
                    default => 'Another item already uses this id.',
                };
                $message = sb_admin_text($selectedLang, $collisionLabel);
            } else {
                if ($originalItemId !== '' && $originalItemId !== $normalized['id']) {
                    shine_bright_delete_content_item($content, $selectedLang, $section, $originalItemId);
                }

                $saved = shine_bright_upsert_content_item($content, $selectedLang, $section, $payload);
                shine_bright_save_content($content);
                $selectedItemId = (string) ($saved['id'] ?? '');
                $selectedMode = in_array($section, ['classes', 'events', 'products'], true) ? 'edit' : $selectedMode;
                $savedLabel = match ($section) {
                    'classes' => 'Classes item saved.',
                    'events' => 'Events item saved.',
                    'products' => 'Products item saved.',
                    'testimonials' => 'Testimonials item saved.',
                    default => 'Item saved.',
                };
                $message = $message !== '' ? $message : sb_admin_text($selectedLang, $savedLabel);
            }
        }
    }

    if ($action === 'delete_item') {
        $section = sb_admin_post('section');
        $deleteId = sb_admin_post('item_id');
        $allowed = shine_bright_content_sections();

        if (in_array($section, $allowed, true) && $deleteId !== '' && shine_bright_delete_content_item($content, $selectedLang, $section, $deleteId)) {
            shine_bright_save_content($content);
            if ($selectedAdminSection === $section && $selectedItemId === $deleteId) {
                $selectedItemId = '';
                $selectedMode = 'list';
            }
            $deletedLabel = match ($section) {
                'classes' => 'Classes item deleted.',
                'events' => 'Events item deleted.',
                'products' => 'Products item deleted.',
                'testimonials' => 'Testimonials item deleted.',
                default => 'Item deleted.',
            };
            $message = sb_admin_text($selectedLang, $deletedLabel);
        }
    }

    if ($action === 'add_item') {
        $section = sb_admin_post('section');
        $allowed = shine_bright_content_sections();
        $templates = shine_bright_content_item_templates();

        if (in_array($section, $allowed, true) && isset($templates[$section])) {
            $seed = match ($section) {
                'classes' => 'new-class',
                'events' => 'new-event',
                'products' => 'new-product',
                'testimonials' => 'new-testimonial',
                default => 'new-item',
            };
            $item = $templates[$section];
            $item['id'] = $seed . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
            $saved = shine_bright_upsert_content_item($content, $selectedLang, $section, $item);
            shine_bright_save_content($content);
            $selectedItemId = (string) ($saved['id'] ?? '');
            $selectedMode = in_array($section, ['classes', 'events', 'products'], true) ? 'edit' : 'list';
            $selectedAdminSection = $section;
            $addedLabel = match ($section) {
                'classes' => 'Classes item added.',
                'events' => 'Events item added.',
                'products' => 'Products item added.',
                'testimonials' => 'Testimonials item added.',
                default => 'Item added.',
            };
            $message = sb_admin_text($selectedLang, $addedLabel);
        }
    }

    if ($action === 'update_reservation') {
        $reservationId = sb_admin_post('reservation_id');
        $reservations = shine_bright_load_reservations();
        $mailOutcome = '';
        $reservationLang = 'bg';
        $brandForEmail = $content['bg']['brand'] ?? [];

        shine_bright_update_reservation_record($reservations, $reservationId, function (array $reservation) use ($content, &$mailOutcome, &$reservationLang, &$brandForEmail): array {
            $previousStatus = (string) ($reservation['status'] ?? 'new');
            $newStatus = sb_admin_post('reservation_status') !== '' ? sb_admin_post('reservation_status') : $previousStatus;
            $reservationLang = in_array(($reservation['lang'] ?? 'bg'), ['bg', 'en'], true) ? (string) $reservation['lang'] : 'bg';
            $brandForEmail = $content[$reservationLang]['brand'] ?? ($content['bg']['brand'] ?? []);

            $reservation['status'] = $newStatus;
            $reservation['attendance'] = sb_admin_post('reservation_attendance') !== '' ? sb_admin_post('reservation_attendance') : (string) ($reservation['attendance'] ?? 'pending');
            $reservation['admin_note'] = sb_admin_post('reservation_admin_note');
            $reservation['updated_at'] = gmdate('c');

            $shouldSendStatusEmail = $newStatus !== $previousStatus
                && in_array($newStatus, ['confirmed', 'waitlisted', 'cancelled'], true)
                && trim((string) ($reservation['email'] ?? '')) !== '';

            if (!$shouldSendStatusEmail) {
                if ($newStatus !== $previousStatus && trim((string) ($reservation['email'] ?? '')) === '') {
                    $mailOutcome = 'Reservation updated. No email was sent because the guest did not provide an email address.';
                }
                return $reservation;
            }

            $emailPayload = shine_bright_compose_reservation_email(
                $newStatus,
                $reservationLang,
                $brandForEmail,
                $reservation,
                (string) ($reservation['admin_note'] ?? '')
            );

            $mailSent = shine_bright_send_multipart_mail(
                (string) $reservation['email'],
                $emailPayload['subject'],
                $emailPayload['body'],
                (string) ($emailPayload['html'] ?? ''),
                (string) ($brandForEmail['contact_email'] ?? '')
            );

            if ($mailSent) {
                $reservation['status_email_sent_at'] = gmdate('c');
                $reservation['status_email_status'] = 'sent';
                $reservation['status_email_last'] = $newStatus;
                $mailOutcome = 'Reservation updated. ' . ucfirst($newStatus) . ' email sent.';
            } else {
                $reservation['status_email_status'] = 'failed';
                $reservation['status_email_last'] = $newStatus;
                $mailOutcome = 'Reservation updated, but the status email could not be sent.';
            }

            return $reservation;
        });

        shine_bright_save_reservations($reservations);
        $message = $mailOutcome !== '' ? $mailOutcome : sb_admin_text($selectedLang, 'Reservation updated.');
    }

    if ($action === 'quick_reservation_update') {
        $reservationId = sb_admin_post('reservation_id');
        $quickStatus = sb_admin_post('quick_status');
        $quickAttendance = sb_admin_post('quick_attendance');
        $reservations = shine_bright_load_reservations();
        $mailOutcome = '';

        shine_bright_update_reservation_record($reservations, $reservationId, function (array $reservation) use ($content, $quickStatus, $quickAttendance, &$mailOutcome): array {
            $previousStatus = (string) ($reservation['status'] ?? 'new');
            if ($quickStatus !== '') {
                $reservation['status'] = $quickStatus;
            }
            if ($quickAttendance !== '') {
                $reservation['attendance'] = $quickAttendance;
            }
            $reservation['updated_at'] = gmdate('c');

            $newStatus = (string) ($reservation['status'] ?? 'new');
            $reservationLang = in_array(($reservation['lang'] ?? 'bg'), ['bg', 'en'], true) ? (string) $reservation['lang'] : 'bg';
            $brandForEmail = $content[$reservationLang]['brand'] ?? ($content['bg']['brand'] ?? []);
            $shouldSendStatusEmail = $newStatus !== $previousStatus
                && in_array($newStatus, ['confirmed', 'waitlisted', 'cancelled'], true)
                && trim((string) ($reservation['email'] ?? '')) !== '';

            if ($shouldSendStatusEmail) {
                $emailPayload = shine_bright_compose_reservation_email(
                    $newStatus,
                    $reservationLang,
                    $brandForEmail,
                    $reservation,
                    (string) ($reservation['admin_note'] ?? '')
                );
                $mailSent = shine_bright_send_multipart_mail(
                    (string) $reservation['email'],
                    $emailPayload['subject'],
                    $emailPayload['body'],
                    (string) ($emailPayload['html'] ?? ''),
                    (string) ($brandForEmail['contact_email'] ?? '')
                );

                if ($mailSent) {
                    $reservation['status_email_sent_at'] = gmdate('c');
                    $reservation['status_email_status'] = 'sent';
                    $reservation['status_email_last'] = $newStatus;
                    $mailOutcome = ' ' . sb_admin_text($reservationLang, 'Reservation email sent.');
                } else {
                    $reservation['status_email_status'] = 'failed';
                    $reservation['status_email_last'] = $newStatus;
                    $mailOutcome = ' ' . sb_admin_text($reservationLang, 'Reservation email could not be sent.');
                }
            }

            return $reservation;
        });

        shine_bright_save_reservations($reservations);
        $message = sb_admin_text($selectedLang, 'Quick reservation update saved.') . $mailOutcome;
    }

    if ($action === 'resend_reservation_email') {
        $reservationId = sb_admin_post('reservation_id');
        $reservations = shine_bright_load_reservations();
        $mailOutcome = sb_admin_text($selectedLang, 'Reservation email could not be sent.');

        shine_bright_update_reservation_record($reservations, $reservationId, function (array $reservation) use ($content, &$mailOutcome): array {
            $email = trim((string) ($reservation['email'] ?? ''));
            if ($email === '') {
                $mailOutcome = sb_admin_text((string) ($reservation['lang'] ?? 'bg'), 'No email address provided.');
                return $reservation;
            }

            $reservationLang = in_array(($reservation['lang'] ?? 'bg'), ['bg', 'en'], true) ? (string) $reservation['lang'] : 'bg';
            $brandForEmail = $content[$reservationLang]['brand'] ?? ($content['bg']['brand'] ?? []);
            $mode = in_array((string) ($reservation['status'] ?? ''), ['confirmed', 'waitlisted', 'cancelled'], true)
                ? (string) ($reservation['status'] ?? '')
                : 'received';
            $emailPayload = shine_bright_compose_reservation_email(
                $mode,
                $reservationLang,
                $brandForEmail,
                $reservation,
                (string) ($reservation['admin_note'] ?? '')
            );
            $mailSent = shine_bright_send_multipart_mail(
                $email,
                $emailPayload['subject'],
                $emailPayload['body'],
                (string) ($emailPayload['html'] ?? ''),
                (string) ($brandForEmail['contact_email'] ?? '')
            );

            if ($mailSent) {
                if ($mode === 'received') {
                    $reservation['ack_email_sent_at'] = gmdate('c');
                    $reservation['ack_email_status'] = 'sent';
                } else {
                    $reservation['status_email_sent_at'] = gmdate('c');
                    $reservation['status_email_status'] = 'sent';
                    $reservation['status_email_last'] = $mode;
                }
                $mailOutcome = sb_admin_text($reservationLang, 'Reservation email sent.');
            } else {
                if ($mode === 'received') {
                    $reservation['ack_email_status'] = 'failed';
                } else {
                    $reservation['status_email_status'] = 'failed';
                    $reservation['status_email_last'] = $mode;
                }
                $mailOutcome = sb_admin_text($reservationLang, 'Reservation email could not be sent.');
            }

            $reservation['updated_at'] = gmdate('c');
            return $reservation;
        });

        shine_bright_save_reservations($reservations);
        $message = $mailOutcome;
    }
}

$langContent = $content[$selectedLang];
$brand = $langContent['brand'];
$meta = $langContent['meta'];
$inquiryMeta = shine_bright_load_inquiry_meta();
$inquiries = sb_admin_prepare_inquiries(shine_bright_read_inquiries(200), $inquiryMeta);
$productOrders = sb_admin_product_orders($inquiries);
$selectedInquiry = $selectedInquiryId !== '' ? sb_admin_find_inquiry($inquiries, $selectedInquiryId) : null;
$reservations = shine_bright_load_reservations();
$visitPacks = shine_bright_load_visit_packs();
usort($visitPacks, static function (array $left, array $right): int {
    $leftSort = (string) (($left['created_at'] ?? '') !== '' ? $left['created_at'] : ($left['updated_at'] ?? ''));
    $rightSort = (string) (($right['created_at'] ?? '') !== '' ? $right['created_at'] : ($right['updated_at'] ?? ''));
    return sb_admin_compare_desc($leftSort, $rightSort);
});
$selectedVisitPerPage = sb_admin_visit_per_page($selectedVisitPerPage);
$visitPackTotalCount = count($visitPacks);
$visitPackPageCount = $selectedVisitPerPage === 'all' ? 1 : max(1, (int) ceil($visitPackTotalCount / max(1, (int) $selectedVisitPerPage)));
$selectedVisitPageNumber = $selectedVisitPerPage === 'all' ? 1 : max(1, min((int) $selectedVisitPage, $visitPackPageCount));
$selectedVisitPage = (string) $selectedVisitPageNumber;
$paginatedVisitPacks = $selectedVisitPerPage === 'all'
    ? $visitPacks
    : array_slice($visitPacks, ($selectedVisitPageNumber - 1) * (int) $selectedVisitPerPage, (int) $selectedVisitPerPage);
$visitPackRangeStart = $visitPackTotalCount === 0 ? 0 : (($selectedVisitPerPage === 'all' ? 0 : (($selectedVisitPageNumber - 1) * (int) $selectedVisitPerPage)) + 1);
$visitPackRangeEnd = $selectedVisitPerPage === 'all'
    ? $visitPackTotalCount
    : min($visitPackTotalCount, $visitPackRangeStart + count($paginatedVisitPacks) - 1);
if ($visitPackTotalCount === 0) {
    $visitPackRangeEnd = 0;
}
$visitUsage = shine_bright_load_visit_usage();
$studentActivationTokens = shine_bright_load_student_activation_tokens();
$clients = shine_bright_client_runtime_index(
    shine_bright_load_clients(),
    $visitPacks,
    $visitUsage,
    $studentActivationTokens
);
$contactLeads = shine_bright_contact_lead_index($clients, $reservations, $inquiries);
$classItems = shine_bright_content_section_items($content, $selectedLang, 'classes');
$eventItems = shine_bright_content_section_items($content, $selectedLang, 'events');
$productItems = shine_bright_content_section_items($content, $selectedLang, 'products');
$testimonialItems = shine_bright_content_section_items($content, $selectedLang, 'testimonials');
$selectedClassItem = $selectedAdminSection === 'classes' && $selectedItemId !== ''
    ? shine_bright_find_content_item($content, $selectedLang, 'classes', $selectedItemId)
    : null;
$selectedEventItem = $selectedAdminSection === 'events' && $selectedItemId !== ''
    ? shine_bright_find_content_item($content, $selectedLang, 'events', $selectedItemId)
    : null;
$selectedProductItem = $selectedAdminSection === 'products' && $selectedItemId !== ''
    ? shine_bright_find_content_item($content, $selectedLang, 'products', $selectedItemId)
    : null;
$selectedTestimonialItem = $selectedAdminSection === 'testimonials' && $selectedItemId !== ''
    ? shine_bright_find_content_item($content, $selectedLang, 'testimonials', $selectedItemId)
    : null;
$selectedClientItem = $selectedAdminSection === 'clients' && $selectedItemId !== ''
    ? shine_bright_find_record_by_id($clients, $selectedItemId)
    : null;
$selectedVisitPackItem = $selectedAdminSection === 'visit-packs' && $selectedItemId !== ''
    ? shine_bright_find_record_by_id($visitPacks, $selectedItemId)
    : null;

if ($selectedAdminSection === 'classes' && $selectedMode === 'edit' && !$selectedClassItem) {
    $selectedMode = 'list';
    $selectedItemId = '';
}

if ($selectedAdminSection === 'events' && $selectedMode === 'edit' && !$selectedEventItem) {
    $selectedMode = 'list';
    $selectedItemId = '';
}

if ($selectedAdminSection === 'products' && $selectedMode === 'edit' && !$selectedProductItem) {
    $selectedMode = 'list';
    $selectedItemId = '';
}

if ($selectedAdminSection === 'testimonials' && $selectedMode === 'edit' && !$selectedTestimonialItem) {
    $selectedMode = 'list';
    $selectedItemId = '';
}

if ($selectedAdminSection === 'clients' && $selectedMode === 'edit' && !$selectedClientItem) {
    $selectedMode = 'list';
    $selectedItemId = '';
}

if ($selectedAdminSection === 'visit-packs' && $selectedMode === 'edit' && !$selectedVisitPackItem) {
    $selectedMode = 'list';
    $selectedItemId = '';
}

$newReservationCount = sb_admin_reservation_count($reservations, 'status', 'new');
$confirmedReservationCount = sb_admin_reservation_count($reservations, 'status', 'confirmed');
$waitlistedReservationCount = sb_admin_reservation_count($reservations, 'status', 'waitlisted');
$attendedReservationCount = sb_admin_reservation_count($reservations, 'attendance', 'attended');
$activeVisitPackCount = count(array_filter($visitPacks, static fn (array $pack): bool => shine_bright_visit_pack_runtime_status($pack) === 'active'));
$lowVisitPackCount = count(array_filter($visitPacks, static fn (array $pack): bool => shine_bright_visit_pack_is_low($pack)));
$allUpcomingAdminSessions = sb_admin_upcoming_sessions($classItems, $reservations, $selectedLang, 7);
$selectedSessionView = null;
if ($selectedSessionViewId !== '') {
    foreach ($allUpcomingAdminSessions as $session) {
        if ((string) ($session['id'] ?? '') === $selectedSessionViewId) {
            $selectedSessionView = $session;
            break;
        }
    }
}
$upcomingAdminSessions = $allUpcomingAdminSessions;
 $filteredReservations = sb_admin_filter_reservations($reservations, $selectedClassFilter);
if ($selectedClassFilter !== '') {
    $upcomingAdminSessions = array_values(array_filter($upcomingAdminSessions, static function (array $session) use ($selectedClassFilter): bool {
        return (string) ($session['class_id'] ?? '') === $selectedClassFilter;
    }));
}

if ($selectedTimeScope === '') {
    $todayDate = (new DateTimeImmutable('today', new DateTimeZone('Europe/Sofia')))->format('Y-m-d');
    $hasTodaySessions = false;
    foreach ($upcomingAdminSessions as $session) {
        if ((string) ($session['date'] ?? '') === $todayDate) {
            $hasTodaySessions = true;
            break;
        }
    }
    $selectedTimeScope = $hasTodaySessions ? 'today' : 'week';
}

$upcomingAdminSessions = sb_admin_filter_sessions_by_scope($upcomingAdminSessions, $selectedTimeScope);

function sb_admin_hidden(string $token, string $lang, string $section, string $classFilter = '', string $selectedItemId = '', string $selectedMode = 'list', string $timeScope = '', string $sessionView = '', string $visitPage = '', string $visitPerPage = ''): string
{
    global $selectedTimeScope, $selectedSessionViewId, $selectedVisitPage, $selectedVisitPerPage;
    if ($timeScope === '') {
        $timeScope = $selectedTimeScope;
    }
    if ($sessionView === '') {
        $sessionView = $selectedSessionViewId;
    }
    if ($visitPage === '') {
        $visitPage = $selectedVisitPage;
    }
    if ($visitPerPage === '') {
        $visitPerPage = $selectedVisitPerPage;
    }

    return '<input type="hidden" name="lang" value="' . htmlspecialchars($lang) . '">' .
        '<input type="hidden" name="admin_section" value="' . htmlspecialchars($section) . '">' .
        '<input type="hidden" name="class_filter" value="' . htmlspecialchars($classFilter) . '">' .
        '<input type="hidden" name="time_scope" value="' . htmlspecialchars($timeScope) . '">' .
        '<input type="hidden" name="session_view" value="' . htmlspecialchars($sessionView) . '">' .
        '<input type="hidden" name="visit_page" value="' . htmlspecialchars($visitPage) . '">' .
        '<input type="hidden" name="visit_per_page" value="' . htmlspecialchars($visitPerPage) . '">' .
        '<input type="hidden" name="selected_item_id" value="' . htmlspecialchars($selectedItemId) . '">' .
        '<input type="hidden" name="selected_mode" value="' . htmlspecialchars($selectedMode) . '">';
}

function sb_admin_item_fields(string $section, array $item): array
{
    $templates = shine_bright_content_item_templates();
    return array_values(array_filter(array_keys($templates[$section] ?? []), static function (string $field) use ($section): bool {
        if ($field === 'id') {
            return false;
        }

        if ($section === 'products' && in_array($field, ['image_focus_x', 'image_focus_y', 'image_zoom'], true)) {
            return false;
        }

        return true;
    }));
}

function sb_admin_input_type(string $field): string
{
    return match ($field) {
        'start_at', 'end_at' => 'datetime-local',
        'price_eur', 'image_focus_x', 'image_focus_y', 'image_zoom' => 'number',
        'founder_image_url', 'image_url', 'instagram', 'maps_url' => 'url',
        default => 'text',
    };
}

function sb_admin_duration_preview(array $item): string
{
    if (($item['start_at'] ?? '') === '' || ($item['end_at'] ?? '') === '') {
        return '';
    }

    try {
        $start = new DateTime($item['start_at']);
        $end = new DateTime($item['end_at']);
        $minutes = max(0, (int) round(($end->getTimestamp() - $start->getTimestamp()) / 60));
        return $minutes . ' min';
    } catch (Throwable $e) {
        return '';
    }
}

function sb_admin_media_preview(?string $url): string
{
    if (!is_string($url) || trim($url) === '') {
        return '';
    }

    $url = shine_bright_normalize_public_media_url(trim($url));
    $lower = strtolower($url);

    if (preg_match('/\.(jpg|jpeg|png|webp|gif)(\?.*)?$/', $lower) || str_contains($lower, 'images.unsplash.com') || str_contains($lower, '/media/founder') || str_contains($lower, '/media/hero-poster') || str_contains($lower, '/media/product')) {
        return '<div class="media-preview"><img src="' . htmlspecialchars($url) . '" alt=""></div>';
    }

    if (preg_match('/\.(mp4|webm|mov)(\?.*)?$/', $lower) || str_contains($lower, '/media/hero-video')) {
        return '<div class="media-preview media-preview-video"><video src="' . htmlspecialchars($url) . '" controls preload="metadata"></video></div>';
    }

    return '<p class="media-link"><a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener noreferrer">Open current media</a></p>';
}

function sb_admin_reservation_class_meta(array $classes, string $lang = 'bg'): array
{
    $map = [];
    foreach ($classes as $class) {
        $id = (string) ($class['id'] ?? '');
        if ($id === '') {
            continue;
        }

        $summary = sb_admin_class_schedule_summary_text($class, $lang);
        if ($summary === '') {
            $timeBits = [];
            if (($class['start_at'] ?? '') !== '') {
                $timeBits[] = str_replace('T', ' ', (string) $class['start_at']);
            }
            if (($class['location'] ?? '') !== '') {
                $timeBits[] = (string) $class['location'];
            }
            $summary = trim(implode(' · ', $timeBits));
        }

        $map[$id] = $summary;
    }

    return $map;
}

function sb_admin_reservation_count(array $reservations, string $field, string $value): int
{
    $count = 0;
    foreach ($reservations as $reservation) {
        if (($reservation[$field] ?? '') === $value) {
            $count++;
        }
    }

    return $count;
}

function sb_admin_select_option(string $value, string $current, string $label): string
{
    $selected = $value === $current ? ' selected' : '';
    return '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
}

function sb_admin_class_reservation_counts(array $reservations, string $classId): array
{
    $counts = [
        'total' => 0,
        'new' => 0,
        'confirmed' => 0,
        'waitlisted' => 0,
        'cancelled' => 0,
        'attended' => 0,
    ];

    foreach ($reservations as $reservation) {
        if (($reservation['class_id'] ?? '') !== $classId) {
            continue;
        }

        $counts['total']++;
        $status = (string) ($reservation['status'] ?? '');
        $attendance = (string) ($reservation['attendance'] ?? '');

        if (isset($counts[$status])) {
            $counts[$status]++;
        }
        if ($attendance === 'attended') {
            $counts['attended']++;
        }
    }

    return $counts;
}

function sb_admin_session_reservation_count(array $reservations, string $sessionId): int
{
    $count = 0;
    foreach ($reservations as $reservation) {
        if ((string) ($reservation['session_id'] ?? '') === $sessionId) {
            $count++;
        }
    }

    return $count;
}

function sb_admin_reservation_matches_session(array $reservation, array $session): bool
{
    $reservationSessionId = (string) ($reservation['session_id'] ?? '');
    if ($reservationSessionId !== '') {
        return $reservationSessionId === (string) ($session['id'] ?? '');
    }

    if ((string) ($reservation['class_id'] ?? '') !== (string) ($session['class_id'] ?? '')) {
        return false;
    }

    $reservationScheduleId = (string) ($reservation['schedule_id'] ?? '');
    if ($reservationScheduleId !== '' && $reservationScheduleId !== (string) ($session['schedule_id'] ?? '')) {
        return false;
    }

    $reservationLabel = trim((string) ($reservation['session_label'] ?? ''));
    if ($reservationLabel !== '') {
        return $reservationLabel === trim((string) ($session['summary_label'] ?? ''));
    }

    $dateLabel = trim((string) ($reservation['class_date_label'] ?? ''));
    $timeLabel = trim((string) ($reservation['class_time_label'] ?? ''));
    return $dateLabel !== '' && $timeLabel !== ''
        && $dateLabel === trim((string) ($session['date_label'] ?? ''))
        && $timeLabel === trim((string) ($session['time_label'] ?? ''));
}

function sb_admin_session_reservations(array $reservations, array $session): array
{
    $items = array_values(array_filter($reservations, static function (array $reservation) use ($session): bool {
        return sb_admin_reservation_matches_session($reservation, $session);
    }));

    usort($items, static function (array $left, array $right): int {
        return strcmp((string) ($left['created_at'] ?? ''), (string) ($right['created_at'] ?? ''));
    });

    return $items;
}

function sb_admin_reservation_status_counts(array $reservations): array
{
    $counts = [
        'total' => count($reservations),
        'new' => 0,
        'confirmed' => 0,
        'waitlisted' => 0,
        'attended' => 0,
    ];

    foreach ($reservations as $reservation) {
        $status = (string) ($reservation['status'] ?? '');
        $attendance = (string) ($reservation['attendance'] ?? '');
        if (isset($counts[$status])) {
            $counts[$status]++;
        }
        if ($attendance === 'attended') {
            $counts['attended']++;
        }
    }

    return $counts;
}

function sb_admin_upcoming_sessions(array $classes, array $reservations, string $lang, int $daysAhead = 7): array
{
    $sessions = [];
    foreach ($classes as $class) {
        foreach (shine_bright_class_upcoming_sessions($class, $lang, $daysAhead, 16) as $session) {
            $session['reservation_count'] = sb_admin_session_reservation_count($reservations, (string) ($session['id'] ?? ''));
            $sessions[] = $session;
        }
    }

    usort($sessions, static function (array $left, array $right): int {
        return strcmp((string) ($left['starts_at'] ?? ''), (string) ($right['starts_at'] ?? ''));
    });

    return $sessions;
}

function sb_admin_filter_reservations(array $reservations, string $classId): array
{
    if ($classId === '') {
        return $reservations;
    }

    return array_values(array_filter($reservations, static function (array $reservation) use ($classId): bool {
        return (string) ($reservation['class_id'] ?? '') === $classId;
    }));
}

function sb_admin_filter_sessions_by_scope(array $sessions, string $timeScope): array
{
    if ($sessions === [] || $timeScope === '' || $timeScope === 'week') {
        return $sessions;
    }

    $today = new DateTimeImmutable('today', new DateTimeZone('Europe/Sofia'));
    $targetDate = $timeScope === 'tomorrow'
        ? $today->modify('+1 day')->format('Y-m-d')
        : $today->format('Y-m-d');

    return array_values(array_filter($sessions, static function (array $session) use ($targetDate): bool {
        return (string) ($session['date'] ?? '') === $targetDate;
    }));
}

function sb_admin_format_admin_datetime(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    try {
        $date = new DateTimeImmutable($value);
        return $date->setTimezone(new DateTimeZone('Europe/Sofia'))->format('d.m, H:i');
    } catch (Throwable) {
        return $value;
    }
}

function sb_admin_reservation_email_status(array $reservation, string $kind): array
{
    if ($kind === 'ack') {
        $status = (string) ($reservation['ack_email_status'] ?? ((string) ($reservation['ack_email_sent_at'] ?? '') !== '' ? 'sent' : 'not_sent'));
        $sentAt = (string) ($reservation['ack_email_sent_at'] ?? '');
        $last = 'received';
    } else {
        $status = (string) ($reservation['status_email_status'] ?? ((string) ($reservation['status_email_sent_at'] ?? '') !== '' ? 'sent' : 'not_sent'));
        $sentAt = (string) ($reservation['status_email_sent_at'] ?? '');
        $last = (string) ($reservation['status_email_last'] ?? '');
    }

    return [
        'status' => $status,
        'sent_at' => $sentAt,
        'last' => $last,
    ];
}

function sb_admin_reservation_email_status_label(string $status): string
{
    return match ($status) {
        'sent' => 'Sent',
        'failed' => 'Failed',
        'not_applicable' => 'Not applicable',
        default => 'Not sent yet',
    };
}

function sb_admin_contact_status_label(array $lead): string
{
    $status = (string) ($lead['status'] ?? 'lead');
    return match ($status) {
        'attended' => 'Attended',
        'reserved' => 'Reserved',
        default => 'Lead',
    };
}

function sb_admin_query(array $params): string
{
    unset($params['token']);
    return './admin.php?' . http_build_query($params);
}

function sb_admin_compare_desc(string $left, string $right): int
{
    return strcmp($right, $left);
}

function sb_admin_inquiry_id(array $inquiry): string
{
    $encoded = json_encode($inquiry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return substr(hash('sha256', is_string($encoded) ? $encoded : serialize($inquiry)), 0, 16);
}

function sb_admin_prepare_inquiries(array $inquiries, array $metaRecords = []): array
{
    $prepared = array_values(array_map(static function (array $inquiry) use ($metaRecords): array {
        $inquiry['_id'] = sb_admin_inquiry_id($inquiry);
        $meta = $metaRecords[$inquiry['_id']] ?? shine_bright_inquiry_meta_template();
        $inquiry['_meta'] = $meta;
        $inquiry['order_status'] = (string) ($meta['order_status'] ?? 'new');
        return $inquiry;
    }, $inquiries));

    return array_values(array_filter($prepared, static function (array $inquiry): bool {
        return trim((string) (($inquiry['_meta']['deleted_at'] ?? ''))) === '';
    }));
}

function sb_admin_find_inquiry(array $inquiries, string $inquiryId): ?array
{
    foreach ($inquiries as $inquiry) {
        if ((string) ($inquiry['_id'] ?? '') === $inquiryId) {
            return $inquiry;
        }
    }

    return null;
}

function sb_admin_inquiry_detail_rows(array $inquiry): array
{
    return [
        'When' => (string) ($inquiry['ts'] ?? ''),
        'Type' => (string) ($inquiry['inquiry_type'] ?? ''),
        'Name' => (string) ($inquiry['customer_name'] ?? ''),
        'Contact' => (string) (($inquiry['email'] ?? '') !== '' ? $inquiry['email'] : ($inquiry['phone'] ?? '')),
        'Item' => (string) ($inquiry['item_title'] ?? ''),
        'Item ID' => (string) ($inquiry['item_id'] ?? ''),
        'Session ID' => (string) ($inquiry['session_id'] ?? ''),
        'Schedule ID' => (string) ($inquiry['schedule_id'] ?? ''),
        'Quantity' => (string) ($inquiry['quantity'] ?? ''),
        'Source path' => (string) ($inquiry['source_path'] ?? ''),
        'Message' => (string) ($inquiry['message'] ?? ''),
        'IP hash' => (string) ($inquiry['ip_hash'] ?? ''),
        'User agent' => (string) ($inquiry['ua'] ?? ''),
    ];
}

function sb_admin_inquiry_type_label(array $inquiry): string
{
    $type = trim((string) ($inquiry['inquiry_type'] ?? ''));
    return $type !== '' ? ucfirst($type) : 'Inquiry';
}

function sb_admin_product_orders(array $inquiries): array
{
    return array_values(array_filter($inquiries, static function (array $inquiry): bool {
        return (string) ($inquiry['inquiry_type'] ?? '') === 'product';
    }));
}

function sb_admin_order_status_label(string $status): string
{
    return match ($status) {
        'confirmed' => 'Confirmed order',
        'cancelled' => 'Cancelled',
        'shipped' => 'Shipped order',
        default => 'New order',
    };
}

function sb_admin_visit_per_page(string $value): string
{
    return in_array($value, ['10', '25', '50', 'all'], true) ? $value : '10';
}

function sb_admin_public_item_path(string $section, string $id, string $lang): string
{
    return '/' . $section . '/' . rawurlencode($id) . '?lang=' . urlencode($lang);
}

function sb_admin_nav_items(): array
{
    return [
        'dashboard' => 'Dashboard',
        'brand' => 'Brand',
        'media' => 'Media',
        'seo' => 'SEO',
        'classes' => 'Classes',
        'events' => 'Events',
        'products' => 'Products',
        'clients' => 'Students',
        'contacts' => 'Contacts',
        'visit-packs' => 'Visit Cards',
        'testimonials' => 'Testimonials',
        'bookings' => 'Class Bookings',
        'inquiries' => 'Inquiries',
        'product-orders' => 'Product Orders',
        'ui' => 'UI Copy',
    ];
}

function sb_admin_class_editor_defaults(?array $item): array
{
    $template = shine_bright_content_item_templates()['classes'];
    if (!$item) {
        $template['schedules'] = [$template['schedules'][0] ?? shine_bright_normalize_class_schedule([], 0)];
        return $template;
    }

    $merged = array_replace($template, $item);
    $merged['schedules'] = shine_bright_class_schedules($item);
    if ($merged['schedules'] === []) {
        $merged['schedules'] = [shine_bright_normalize_class_schedule([], 0)];
    }
    return $merged;
}

function sb_admin_event_editor_defaults(?array $item): array
{
    $template = shine_bright_content_item_templates()['events'];
    if (!$item) {
        return $template;
    }

    return array_replace($template, $item);
}

function sb_admin_product_editor_defaults(?array $item): array
{
    $template = shine_bright_content_item_templates()['products'];
    if (!$item) {
        return $template;
    }

    return array_replace($template, $item);
}

function sb_admin_testimonial_editor_defaults(?array $item): array
{
    $template = shine_bright_content_item_templates()['testimonials'];
    if (!$item) {
        return $template;
    }

    return array_replace($template, $item);
}

function sb_admin_client_editor_defaults(?array $item): array
{
    $template = shine_bright_client_template();
    if (!$item) {
        return $template;
    }

    return array_replace($template, $item);
}

function sb_admin_visit_pack_editor_defaults(?array $item): array
{
    $template = shine_bright_visit_pack_template();
    if (!$item) {
        return $template;
    }

    $merged = array_replace($template, $item);
    $merged['applies_to_class_ids'] = shine_bright_visit_pack_allowed_class_ids($item);
    return $merged;
}

function sb_admin_client_options(array $clients): string
{
    $options = '<option value="">Choose client</option>';
    foreach ($clients as $client) {
        $label = trim((string) ($client['name'] ?? '')) ?: (string) ($client['id'] ?? '');
        $options .= '<option value="' . htmlspecialchars((string) ($client['id'] ?? '')) . '">' . htmlspecialchars($label) . '</option>';
    }

    return $options;
}

function sb_admin_find_client_name(array $clients, string $clientId): string
{
    $client = shine_bright_find_record_by_id($clients, $clientId);
    if (!$client) {
        return $clientId;
    }

    return trim((string) ($client['name'] ?? '')) ?: $clientId;
}

function sb_admin_find_class_title(array $classes, string $classId): string
{
    if ($classId === '') {
        return 'All classes';
    }

    foreach ($classes as $class) {
        if ((string) ($class['id'] ?? '') !== $classId) {
            continue;
        }

        return trim((string) ($class['title'] ?? '')) ?: $classId;
    }

    return $classId;
}

function sb_admin_find_class_titles(array $classes, array $classIds, string $lang): string
{
    if ($classIds === []) {
        return sb_admin_text($lang, 'All classes');
    }

    $titles = [];
    foreach ($classIds as $classId) {
        $titles[] = sb_admin_find_class_title($classes, $classId);
    }

    return implode(', ', array_unique($titles));
}

function sb_admin_visit_pack_status_badge(string $lang, array $pack): string
{
    $status = shine_bright_visit_pack_runtime_status($pack);
    $label = match ($status) {
        'completed' => 'Completed',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled',
        default => shine_bright_visit_pack_is_low($pack) ? 'Low' : 'Active',
    };
    $class = 'status-badge status-' . htmlspecialchars($status === 'active' && shine_bright_visit_pack_is_low($pack) ? 'low' : $status);

    return '<span class="' . $class . '">' . htmlspecialchars(sb_admin_text($lang, $label)) . '</span>';
}

function sb_admin_format_pack_expiry(array $pack): string
{
    $expiresOn = trim((string) ($pack['expires_on'] ?? ''));
    return $expiresOn !== '' ? $expiresOn : 'No expiry';
}

function sb_admin_breadcrumbs(string $token, string $lang, string $section, string $mode, string $label, string $itemId = ''): string
{
    $parts = [];
    $parts[] = '<a href="' . htmlspecialchars(sb_admin_query(['lang' => $lang, 'section' => 'dashboard'])) . '">' . htmlspecialchars(sb_admin_text($lang, 'Admin')) . '</a>';
    $parts[] = '<a href="' . htmlspecialchars(sb_admin_query(['lang' => $lang, 'section' => $section])) . '">' . htmlspecialchars(sb_admin_text($lang, $label)) . '</a>';

    if ($mode === 'create') {
        $parts[] = '<span>' . htmlspecialchars(sb_admin_text($lang, 'Add')) . '</span>';
    } elseif ($mode === 'edit') {
        $parts[] = '<span>' . htmlspecialchars($itemId !== '' ? $itemId : 'Edit') . '</span>';
    }

    return '<nav class="breadcrumbs" aria-label="Breadcrumbs">' . implode('<span class="breadcrumb-sep">/</span>', $parts) . '</nav>';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Shine Bright Yoga Admin</title>
  <style>
    :root {
      --bg: #f3f5ef;
      --surface: #ffffff;
      --surface-soft: #eef2ea;
      --ink: #1d251f;
      --muted: #5f6b62;
      --primary: #5d7666;
      --outline: rgba(93,118,102,0.18);
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: ui-sans-serif, system-ui, sans-serif;
      color: var(--ink);
      background: linear-gradient(180deg, #f4f6f1 0%, #edf1eb 100%);
    }
    .wrap {
      width: min(1200px, calc(100% - 32px));
      margin: 24px auto 40px;
    }
    .topbar, .panel, .item-card {
      background: var(--surface);
      border: 1px solid var(--outline);
      border-radius: 20px;
      box-shadow: 0 20px 50px rgba(24, 35, 27, 0.05);
    }
    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      padding: 18px 22px;
      margin-bottom: 18px;
    }
    .topbar-actions {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-left: auto;
      flex-wrap: wrap;
      justify-content: flex-end;
    }
    .lang-links a {
      display: inline-block;
      margin-left: 8px;
      padding: 8px 12px;
      border-radius: 999px;
      text-decoration: none;
      color: var(--muted);
      background: var(--surface-soft);
    }
    .admin-nav {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin: 0 0 18px;
      padding: 14px;
      background: rgba(255,255,255,0.72);
      border: 1px solid var(--outline);
      border-radius: 18px;
      box-shadow: 0 20px 50px rgba(24, 35, 27, 0.04);
    }
    .admin-nav a {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 42px;
      padding: 10px 16px;
      border-radius: 999px;
      text-decoration: none;
      color: var(--muted);
      background: #f7f9f5;
      border: 1px solid transparent;
      font-weight: 700;
    }
    .admin-nav a.active {
      background: var(--primary);
      color: #fff;
      border-color: rgba(93,118,102,0.28);
    }
    .lang-links a.active {
      background: var(--primary);
      color: #fff;
    }
    .logout-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 40px;
      padding: 8px 14px;
      border-radius: 999px;
      text-decoration: none;
      font-weight: 700;
      color: var(--ink);
      background: #f7f9f5;
      border: 1px solid var(--outline);
    }
    .grid {
      display: grid;
      grid-template-columns: 1.05fr 0.95fr;
      gap: 18px;
    }
    .grid.single-grid {
      grid-template-columns: 1fr;
    }
    .panel {
      padding: 22px;
      margin-bottom: 18px;
    }
    h1, h2, h3 {
      margin: 0 0 14px;
      font-family: Georgia, serif;
      font-weight: 600;
    }
    p, label { color: var(--muted); }
    form { display: grid; gap: 12px; }
    .two-col {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }
    input, textarea, select {
      width: 100%;
      padding: 12px 14px;
      border-radius: 14px;
      border: 1px solid var(--outline);
      background: #fbfcfa;
      font: inherit;
      color: var(--ink);
    }
    textarea { min-height: 110px; resize: vertical; }
    button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 0;
      border-radius: 999px;
      padding: 12px 16px;
      font: inherit;
      font-weight: 700;
      cursor: pointer;
      line-height: 1;
    }
    .primary { background: var(--primary); color: #fff; }
    .secondary { background: #e9eee7; color: var(--ink); }
    .danger { background: #f4dfdc; color: #872b1e; }
    .item-card {
      padding: 16px;
      margin-top: 14px;
    }
    .actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-top: 8px;
    }
    .schedule-editor {
      display: grid;
      gap: 14px;
      margin-top: 6px;
    }
    .schedule-editor-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }
    .schedule-list {
      display: grid;
      gap: 14px;
    }
    .schedule-row {
      display: grid;
      gap: 12px;
      padding: 16px;
      border-radius: 18px;
      background: #f7f9f5;
      border: 1px solid var(--outline);
    }
    .schedule-row-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }
    .class-scope-group {
      margin: 0;
      padding: 16px 18px;
      border: 1px solid var(--outline);
      border-radius: 18px;
      background: #f7f9f5;
      display: grid;
      gap: 12px;
    }
    .class-scope-group legend {
      padding: 0 8px;
      font-weight: 700;
      color: var(--ink);
    }
    .class-scope-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 10px;
    }
    .class-scope-option {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 14px;
      border-radius: 14px;
      background: #fff;
      border: 1px solid var(--outline);
      color: var(--ink);
    }
    .class-scope-option input {
      width: 18px;
      height: 18px;
      margin: 0;
      flex: 0 0 18px;
    }
    .status-badge {
      display: inline-flex;
      align-items: center;
      padding: 6px 10px;
      border-radius: 999px;
      font-size: 0.78rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      border: 1px solid var(--outline);
      background: var(--surface-soft);
      color: var(--primary);
    }
    .status-active { background: rgba(93,118,102,0.12); }
    .status-low { background: rgba(184, 138, 53, 0.12); color: #8d6721; }
    .status-completed { background: rgba(43, 82, 60, 0.16); color: #355643; }
    .status-expired,
    .status-cancelled { background: rgba(125, 77, 64, 0.14); color: #7a5348; }
    .message {
      margin-bottom: 16px;
      padding: 14px 18px;
      border-radius: 16px;
      background: #e8f0eb;
      color: #24412d;
      border: 1px solid rgba(93,118,102,0.18);
    }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.92rem;
    }
    th, td {
      text-align: left;
      padding: 10px 8px;
      border-bottom: 1px solid rgba(93,118,102,0.12);
      vertical-align: top;
    }
    .media-preview {
      margin-top: 8px;
      border-radius: 16px;
      overflow: hidden;
      border: 1px solid var(--outline);
      background: #f6f8f4;
    }
    .media-preview img,
    .media-preview video {
      display: block;
      width: 100%;
      max-height: 240px;
      object-fit: cover;
      background: #dfe6dd;
    }
    .media-link {
      margin: 8px 0 0;
      font-size: 0.92rem;
    }
    .reservation-card {
      padding: 16px;
      border: 1px solid var(--outline);
      border-radius: 18px;
      background: #fbfcfa;
      margin-top: 14px;
    }
    .session-overview {
      display: grid;
      gap: 12px;
    }
    .session-card {
      padding: 16px;
      border: 1px solid var(--outline);
      border-radius: 18px;
      background: #fbfcfa;
    }
    .session-view-shell {
      display: grid;
      gap: 16px;
    }
    .session-view-header {
      display: flex;
      justify-content: space-between;
      gap: 16px;
      align-items: flex-start;
      margin-bottom: 10px;
    }
    .session-view-header h3 {
      margin: 0 0 6px;
      font-size: 1.65rem;
    }
    .session-view-header p {
      margin: 0;
      color: var(--muted);
    }
    .session-email-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }
    .session-email-card {
      padding: 14px 16px;
      border-radius: 16px;
      border: 1px solid var(--outline);
      background: #f7f9f5;
      display: grid;
      gap: 6px;
    }
    .session-email-card span {
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--muted);
      font-weight: 700;
    }
    .session-guest-list {
      display: grid;
      gap: 10px;
      margin-top: 14px;
    }
    .session-guest-item {
      padding: 12px 14px;
      border-radius: 14px;
      background: #f5f8f3;
      border: 1px solid var(--outline);
      display: grid;
      gap: 6px;
    }
    .session-guest-top {
      display: flex;
      justify-content: space-between;
      gap: 12px;
      align-items: baseline;
    }
    .session-guest-top strong {
      color: var(--ink);
    }
    .session-guest-meta {
      color: var(--muted);
      font-size: 0.92rem;
      line-height: 1.5;
    }
    .session-guest-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 8px;
    }
    .session-guest-actions form {
      margin: 0;
    }
    .quick-button {
      border-radius: 999px;
      border: 1px solid var(--outline);
      background: #fff;
      color: var(--ink);
      padding: 8px 12px;
      font: inherit;
      font-weight: 600;
      cursor: pointer;
    }
    .quick-button.primary {
      background: var(--primary);
      color: #fff;
      border-color: var(--primary);
    }
    .quick-button.danger {
      color: #8b4d40;
      border-color: rgba(125, 77, 64, 0.2);
      background: rgba(125, 77, 64, 0.08);
    }
    .session-detail-actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin: 12px 0 16px;
    }
    .session-detail-form {
      display: grid;
      gap: 12px;
      margin-top: 8px;
    }
    .session-detail-form-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }
    .session-detail-form label {
      display: grid;
      gap: 6px;
      font-size: 0.9rem;
      color: var(--muted);
    }
    .session-detail-form select,
    .session-detail-form textarea {
      width: 100%;
      padding: 12px 14px;
      border-radius: 14px;
      border: 1px solid var(--outline);
      background: #fff;
      font: inherit;
      color: var(--ink);
    }
    .session-detail-form textarea {
      min-height: 82px;
      resize: vertical;
    }
    .session-note-shell {
      margin-top: 10px;
    }
    .session-note-shell summary {
      cursor: pointer;
      color: var(--primary);
      font-weight: 700;
    }
    .usage-list {
      display: grid;
      gap: 12px;
      margin-top: 18px;
    }
    .usage-item {
      padding: 14px 16px;
      border: 1px solid var(--outline);
      border-radius: 16px;
      background: #fbfcfa;
    }
    .usage-item strong {
      display: block;
      margin-bottom: 6px;
    }
    .session-top {
      display: flex;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 12px;
    }
    .session-top p {
      margin: 6px 0 0;
      font-size: 0.94rem;
    }
    .session-stats {
      display: grid;
      grid-template-columns: repeat(5, minmax(0, 1fr));
      gap: 8px;
      margin-bottom: 14px;
    }
    .session-stats div {
      padding: 10px 12px;
      border-radius: 14px;
      background: #f5f8f3;
      border: 1px solid var(--outline);
    }
    .session-stats strong {
      display: block;
      font-size: 1.05rem;
      color: var(--ink);
    }
    .panel-note {
      margin: -2px 0 16px;
      font-size: 0.94rem;
      color: var(--muted);
    }
    .breadcrumbs {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 8px;
      margin-bottom: 14px;
      font-size: 0.9rem;
      color: var(--muted);
    }
    .breadcrumbs a {
      color: var(--muted);
      text-decoration: none;
      font-weight: 600;
    }
    .breadcrumbs a:hover {
      color: var(--ink);
    }
    .breadcrumb-sep {
      color: rgba(93,118,102,0.52);
    }
    .quick-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 12px;
      margin-bottom: 18px;
    }
    .quick-stat {
      padding: 16px;
      border-radius: 18px;
      border: 1px solid var(--outline);
      background: #fbfcfa;
    }
    .quick-stat strong {
      display: block;
      margin-bottom: 6px;
      font-size: 1.45rem;
      color: var(--ink);
    }
    .shortcut-list {
      display: grid;
      gap: 12px;
    }
    .shortcut-card {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 16px;
      border-radius: 18px;
      border: 1px solid var(--outline);
      background: #fbfcfa;
    }
    .section-stack {
      display: grid;
      gap: 18px;
    }
    .inline-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 46px;
      padding: 12px 16px;
      border-radius: 999px;
      text-decoration: none;
      font-weight: 700;
      background: #e9eee7;
      color: var(--ink);
      border: 1px solid var(--outline);
    }
    .inquiry-detail {
      display: grid;
      gap: 14px;
      margin-bottom: 18px;
    }
    .inquiry-detail-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }
    .inquiry-detail-card {
      padding: 14px 16px;
      border-radius: 16px;
      background: #fbfcfa;
      border: 1px solid var(--outline);
    }
    .inquiry-detail-card strong {
      display: block;
      margin-bottom: 6px;
      font-size: 0.86rem;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .inquiry-detail-card p {
      margin: 0;
      color: var(--ink);
      line-height: 1.55;
      word-break: break-word;
    }
    .scope-switch {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin: 18px 0 6px;
    }
    .scope-switch a {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 42px;
      padding: 0 16px;
      border-radius: 999px;
      text-decoration: none;
      font-weight: 700;
      background: #eef2ea;
      color: var(--ink);
      border: 1px solid var(--outline);
    }
    .scope-switch a.active {
      background: var(--primary);
      color: #fff;
      border-color: transparent;
    }
    .reservation-top {
      display: flex;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 12px;
    }
    .reservation-meta {
      color: var(--muted);
      font-size: 0.92rem;
      line-height: 1.55;
    }
    .reservation-stats {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 10px;
      margin-bottom: 18px;
    }
    .reservation-stats div {
      padding: 12px 14px;
      border-radius: 16px;
      background: #fbfcfa;
      border: 1px solid var(--outline);
    }
    .reservation-stats strong {
      display: block;
      font-size: 1.3rem;
      color: var(--ink);
    }
    @media (max-width: 900px) {
      .grid, .two-col { grid-template-columns: 1fr; }
      .topbar { flex-direction: column; align-items: flex-start; }
      .topbar-actions { width: 100%; justify-content: space-between; margin-left: 0; }
      .reservation-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .session-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .quick-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .inquiry-detail-grid { grid-template-columns: 1fr; }
      .schedule-row-grid, .class-scope-grid { grid-template-columns: 1fr; }
      .session-detail-form-grid,
      .session-email-grid { grid-template-columns: 1fr; }
      .session-view-header { flex-direction: column; }
    }
    .product-image-preview {
      width: 100%;
      min-height: 200px;
      max-height: 320px;
      border-radius: 18px;
      background-color: #f0f4ed;
      background-size: cover;
      background-repeat: no-repeat;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 1px solid var(--outline);
      overflow: hidden;
    }
    .range-wrap {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .range-wrap input[type="range"] {
      flex: 1;
      accent-color: var(--primary);
    }
    .range-wrap input[type="number"] {
      width: 64px;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div>
        <h1>Shine Bright Yoga Admin</h1>
        <p><?= htmlspecialchars(sb_admin_text($selectedLang, 'Manage brand content, classes, events, products, clients, visit cards, and class bookings.')) ?></p>
      </div>
      <div class="topbar-actions">
        <div class="lang-links">
          <a class="<?= $selectedLang === 'bg' ? 'active' : '' ?>" href="<?= htmlspecialchars(sb_admin_query(['lang' => 'bg', 'section' => $selectedAdminSection, 'class_filter' => $selectedClassFilter, 'time_scope' => $selectedTimeScope, 'inquiry' => in_array($selectedAdminSection, ['dashboard', 'bookings', 'inquiries', 'product-orders'], true) ? $selectedInquiryId : '', 'visit_page' => $selectedAdminSection === 'visit-packs' ? $selectedVisitPage : '', 'visit_per_page' => $selectedAdminSection === 'visit-packs' ? $selectedVisitPerPage : ''])) ?>">BG</a>
          <a class="<?= $selectedLang === 'en' ? 'active' : '' ?>" href="<?= htmlspecialchars(sb_admin_query(['lang' => 'en', 'section' => $selectedAdminSection, 'class_filter' => $selectedClassFilter, 'time_scope' => $selectedTimeScope, 'inquiry' => in_array($selectedAdminSection, ['dashboard', 'bookings', 'inquiries', 'product-orders'], true) ? $selectedInquiryId : '', 'visit_page' => $selectedAdminSection === 'visit-packs' ? $selectedVisitPage : '', 'visit_per_page' => $selectedAdminSection === 'visit-packs' ? $selectedVisitPerPage : ''])) ?>">EN</a>
        </div>
        <a class="logout-link" href="./admin-scan.php?lang=<?= htmlspecialchars($selectedLang) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'QR Check-in')) ?></a>
        <a class="logout-link" href="<?= htmlspecialchars(shine_bright_admin_logout_url($selectedLang)) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Log out')) ?></a>
      </div>
    </div>

    <nav class="admin-nav" aria-label="Admin sections">
      <?php foreach (sb_admin_nav_items() as $sectionKey => $sectionLabel): ?>
        <a
          class="<?= $selectedAdminSection === $sectionKey ? 'active' : '' ?>"
          href="<?= htmlspecialchars(sb_admin_query(['lang' => $selectedLang, 'section' => $sectionKey, 'class_filter' => $sectionKey === 'bookings' ? $selectedClassFilter : '', 'time_scope' => in_array($sectionKey, ['dashboard', 'bookings'], true) ? $selectedTimeScope : '', 'inquiry' => in_array($sectionKey, ['dashboard', 'bookings', 'inquiries', 'product-orders'], true) ? $selectedInquiryId : '', 'visit_page' => $sectionKey === 'visit-packs' ? $selectedVisitPage : '', 'visit_per_page' => $sectionKey === 'visit-packs' ? $selectedVisitPerPage : ''])) ?>"
        ><?= htmlspecialchars(sb_admin_text($selectedLang, $sectionLabel)) ?></a>
      <?php endforeach; ?>
    </nav>

    <?php if ($message !== ''): ?>
      <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="grid<?= $selectedAdminSection === 'dashboard' ? '' : ' single-grid' ?>">
      <div>
        <?php if ($selectedAdminSection === 'dashboard'): ?>
          <section class="panel">
            <h2><?= htmlspecialchars(sb_admin_text($selectedLang, 'Dashboard')) ?></h2>
            <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'A calmer overview of the current schedule, class bookings, and active visit-card operations.')) ?></p>
            <div class="quick-grid">
              <div class="quick-stat"><strong><?= count($classItems) ?></strong><span><?= htmlspecialchars(sb_admin_text($selectedLang, 'Upcoming classes')) ?></span></div>
              <div class="quick-stat"><strong><?= $newReservationCount ?></strong><span><?= htmlspecialchars(sb_admin_text($selectedLang, 'New class bookings')) ?></span></div>
              <div class="quick-stat"><strong><?= count($clients) ?></strong><span><?= htmlspecialchars(sb_admin_text($selectedLang, 'Clients')) ?></span></div>
              <div class="quick-stat"><strong><?= $activeVisitPackCount ?></strong><span><?= htmlspecialchars(sb_admin_text($selectedLang, 'Active visit cards')) ?></span></div>
            </div>
            <div class="shortcut-list">
              <div class="shortcut-card">
                <div>
                  <strong><?= htmlspecialchars(sb_admin_text($selectedLang, 'Manage classes')) ?></strong>
                  <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Review the current class list, open one class for editing, or add a new session.')) ?></p>
                </div>
                <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['lang' => $selectedLang, 'section' => 'classes'])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Open classes')) ?></a>
              </div>
              <div class="shortcut-card">
                <div>
                  <strong><?= htmlspecialchars(sb_admin_text($selectedLang, 'Manage events')) ?></strong>
                  <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Review special-format events, open one event for editing, or add a new event.')) ?></p>
                </div>
                <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['lang' => $selectedLang, 'section' => 'events'])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Open events')) ?></a>
              </div>
              <div class="shortcut-card">
                <div>
                  <strong><?= htmlspecialchars(sb_admin_text($selectedLang, 'Manage products')) ?></strong>
                  <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Review the current product list, open one product for editing, or add a new product.')) ?></p>
                </div>
                <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['lang' => $selectedLang, 'section' => 'products'])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Open products')) ?></a>
              </div>
              <div class="shortcut-card">
                <div>
                  <strong><?= htmlspecialchars(sb_admin_text($selectedLang, 'Manage students')) ?></strong>
                  <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Create student records and keep contact details plus notes in one place.')) ?></p>
                </div>
                <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['lang' => $selectedLang, 'section' => 'clients'])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Open students')) ?></a>
              </div>
              <div class="shortcut-card">
                <div>
                  <strong><?= htmlspecialchars(sb_admin_text($selectedLang, 'Manage visit cards')) ?></strong>
                  <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Issue prepaid visit cards, track remaining visits, and record usage history.')) ?></p>
                </div>
                <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['lang' => $selectedLang, 'section' => 'visit-packs'])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Open visit cards')) ?></a>
              </div>
              <div class="shortcut-card">
                <div>
                  <strong><?= htmlspecialchars(sb_admin_text($selectedLang, 'QR Check-in')) ?></strong>
                  <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Scan student QR codes and confirm one visit without opening the full visit-card editor.')) ?></p>
                </div>
                <a class="inline-link" href="./admin-scan.php?lang=<?= htmlspecialchars($selectedLang) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Open QR check-in')) ?></a>
              </div>
              <div class="shortcut-card">
                <div>
                  <strong><?= htmlspecialchars(sb_admin_text($selectedLang, 'Review class bookings')) ?></strong>
                  <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Confirm, waitlist, or cancel guest class bookings and track attendance.')) ?></p>
                </div>
                <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['lang' => $selectedLang, 'section' => 'bookings', 'time_scope' => $selectedTimeScope])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Open class bookings')) ?></a>
              </div>
              <div class="shortcut-card">
                <div>
                  <strong><?= htmlspecialchars(sb_admin_text($selectedLang, 'Update brand')) ?></strong>
                  <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Adjust founder story, homepage messaging, and contact details.')) ?></p>
                </div>
                <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['lang' => $selectedLang, 'section' => 'brand'])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Open brand')) ?></a>
              </div>
              <div class="shortcut-card">
                <div>
                  <strong><?= htmlspecialchars(sb_admin_text($selectedLang, 'Manage media')) ?></strong>
                  <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Update founder image, hero video, poster image, and hero text mode.')) ?></p>
                </div>
                <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['lang' => $selectedLang, 'section' => 'media'])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Open media')) ?></a>
              </div>
            </div>
          </section>
        <?php endif; ?>

        <?php if ($selectedAdminSection === 'brand'): ?>
        <section class="panel">
          <h2><?= htmlspecialchars(sb_admin_text($selectedLang, 'Brand')) ?></h2>
          <form method="post">
            <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
            <input type="hidden" name="action" value="save_brand">
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Headline')) ?><textarea name="brand_headline"><?= htmlspecialchars($brand['headline']) ?></textarea></label>
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Intro')) ?><textarea name="brand_intro"><?= htmlspecialchars($brand['intro']) ?></textarea></label>
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Secondary intro')) ?><textarea name="brand_subintro"><?= htmlspecialchars($brand['subintro']) ?></textarea></label>
            <div class="two-col">
              <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Founder name')) ?><input type="text" name="founder_name" value="<?= htmlspecialchars($brand['founder_name']) ?>"></label>
              <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Founder title')) ?><input type="text" name="founder_title" value="<?= htmlspecialchars($brand['founder_title']) ?>"></label>
            </div>
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Founder story')) ?><textarea name="founder_story"><?= htmlspecialchars($brand['founder_story']) ?></textarea></label>
            <div class="two-col">
              <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Email')) ?><input type="email" name="contact_email" value="<?= htmlspecialchars($brand['contact_email']) ?>"></label>
              <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Phone')) ?><input type="text" name="contact_phone" value="<?= htmlspecialchars($brand['contact_phone']) ?>"></label>
            </div>
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Instagram URL')) ?><input type="text" name="instagram" value="<?= htmlspecialchars($brand['instagram']) ?>"></label>
            <button class="primary" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Save Brand')) ?></button>
          </form>
        </section>
        <?php endif; ?>

        <?php if ($selectedAdminSection === 'media'): ?>
        <section class="panel">
          <h2><?= htmlspecialchars(sb_admin_text($selectedLang, 'Media')) ?></h2>
          <form method="post" enctype="multipart/form-data">
            <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
            <input type="hidden" name="action" value="save_media">
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Hero text mode')) ?>
              <select name="hero_text_mode">
                <?= sb_admin_select_option('auto', (string) ($brand['hero_text_mode'] ?? 'auto'), sb_admin_text($selectedLang, 'Auto')) ?>
                <?= sb_admin_select_option('dark', (string) ($brand['hero_text_mode'] ?? 'auto'), sb_admin_text($selectedLang, 'Dark')) ?>
                <?= sb_admin_select_option('light', (string) ($brand['hero_text_mode'] ?? 'auto'), sb_admin_text($selectedLang, 'Light')) ?>
              </select>
            </label>
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Founder image URL')) ?><input type="text" name="founder_image_url" value="<?= htmlspecialchars($brand['founder_image_url'] ?? '') ?>" placeholder="./media/founder-....jpg"></label>
            <?= sb_admin_media_preview($brand['founder_image_url'] ?? '') ?>
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Upload founder image')) ?><input type="file" name="founder_image_file" accept="image/jpeg,image/png,image/webp"></label>
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'YouTube hero video URL')) ?><input type="text" name="hero_video_url" value="<?= htmlspecialchars($brand['hero_video_url'] ?? '') ?>" placeholder="https://youtube.com/... or ./media/hero-video-....mp4"></label>
            <?= sb_admin_media_preview($brand['hero_video_url'] ?? '') ?>
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Upload hero video')) ?><input type="file" name="hero_video_file" accept="video/mp4,video/webm,video/quicktime"></label>
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Hero video poster URL (optional)')) ?><input type="text" name="hero_video_poster_url" value="<?= htmlspecialchars($brand['hero_video_poster_url'] ?? '') ?>" placeholder="./media/hero-poster-....jpg"></label>
            <?= sb_admin_media_preview($brand['hero_video_poster_url'] ?? '') ?>
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Upload hero poster')) ?><input type="file" name="hero_video_poster_file" accept="image/jpeg,image/png,image/webp"></label>
            <button class="primary" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Save Media')) ?></button>
          </form>
        </section>
        <?php endif; ?>

        <?php if ($selectedAdminSection === 'seo'): ?>
        <section class="panel">
          <h2><?= htmlspecialchars(sb_admin_text($selectedLang, 'SEO')) ?></h2>
          <form method="post">
            <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
            <input type="hidden" name="action" value="save_seo">
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Meta title')) ?><input type="text" name="meta_title" value="<?= htmlspecialchars($meta['title']) ?>"></label>
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Meta description')) ?><textarea name="meta_description"><?= htmlspecialchars($meta['description']) ?></textarea></label>
            <button class="primary" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Save SEO')) ?></button>
          </form>
        </section>
        <?php endif; ?>

        <?php if ($selectedAdminSection === 'ui'): ?>
        <section class="panel">
          <h2>Interface Copy</h2>
          <form method="post">
            <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
            <input type="hidden" name="action" value="save_ui">
            <label>Contact eyebrow<input type="text" name="ui_contact_eyebrow" value="<?= htmlspecialchars($langContent['ui']['contact_eyebrow'] ?? '') ?>"></label>
            <label>Contact heading<textarea name="ui_contact_heading"><?= htmlspecialchars($langContent['ui']['contact_heading'] ?? '') ?></textarea></label>
            <label>Contact support text<textarea name="ui_contact_body"><?= htmlspecialchars($langContent['ui']['contact_body'] ?? '') ?></textarea></label>
            <label>Contact inquiry CTA<input type="text" name="ui_contact_inquiry_cta" value="<?= htmlspecialchars($langContent['ui']['contact_inquiry_cta'] ?? '') ?>"></label>
            <label>Phone CTA<input type="text" name="ui_phone_cta" value="<?= htmlspecialchars($langContent['ui']['phone_cta'] ?? '') ?>"></label>
            <div class="two-col">
              <label>Inquiry default title<input type="text" name="ui_inquiry_default_title" value="<?= htmlspecialchars($langContent['ui']['inquiry_default_title'] ?? '') ?>"></label>
              <label>Close label<input type="text" name="ui_close_dialog" value="<?= htmlspecialchars($langContent['ui']['close_dialog'] ?? '') ?>"></label>
            </div>
            <div class="two-col">
              <label>Inquiry product prefix<input type="text" name="ui_inquiry_product_prefix" value="<?= htmlspecialchars($langContent['ui']['inquiry_product_prefix'] ?? '') ?>"></label>
              <label>Inquiry general prefix<input type="text" name="ui_inquiry_general_prefix" value="<?= htmlspecialchars($langContent['ui']['inquiry_general_prefix'] ?? '') ?>"></label>
            </div>
            <div class="two-col">
              <label>General dialog title<input type="text" name="ui_general_dialog_title" value="<?= htmlspecialchars($langContent['ui']['general_dialog_title'] ?? '') ?>"></label>
              <label>Reserve dialog title<input type="text" name="ui_reserve_dialog_title" value="<?= htmlspecialchars($langContent['ui']['reserve_dialog_title'] ?? '') ?>"></label>
            </div>
            <div class="two-col">
              <label>Event dialog title<input type="text" name="ui_event_dialog_title" value="<?= htmlspecialchars($langContent['ui']['event_dialog_title'] ?? '') ?>"></label>
              <label>Product dialog title<input type="text" name="ui_product_dialog_title" value="<?= htmlspecialchars($langContent['ui']['product_dialog_title'] ?? '') ?>"></label>
            </div>
            <div class="two-col">
              <label>Reserve context line<input type="text" name="ui_reserve_context" value="<?= htmlspecialchars($langContent['ui']['reserve_context'] ?? '') ?>"></label>
              <label>Event context line<input type="text" name="ui_event_context" value="<?= htmlspecialchars($langContent['ui']['event_context'] ?? '') ?>"></label>
            </div>
            <div class="two-col">
              <label>Product context line<input type="text" name="ui_product_context" value="<?= htmlspecialchars($langContent['ui']['product_context'] ?? '') ?>"></label>
              <label>General context line<input type="text" name="ui_general_context" value="<?= htmlspecialchars($langContent['ui']['general_context'] ?? '') ?>"></label>
            </div>
            <div class="two-col">
              <label>Class type label<input type="text" name="ui_type_label_class" value="<?= htmlspecialchars($langContent['ui']['type_label_class'] ?? '') ?>"></label>
              <label>Event type label<input type="text" name="ui_type_label_event" value="<?= htmlspecialchars($langContent['ui']['type_label_event'] ?? '') ?>"></label>
            </div>
            <div class="two-col">
              <label>Product type label<input type="text" name="ui_type_label_product" value="<?= htmlspecialchars($langContent['ui']['type_label_product'] ?? '') ?>"></label>
              <label>General type label<input type="text" name="ui_type_label_general" value="<?= htmlspecialchars($langContent['ui']['type_label_general'] ?? '') ?>"></label>
            </div>
            <div class="two-col">
              <label>Reserve submit CTA<input type="text" name="ui_reserve_submit" value="<?= htmlspecialchars($langContent['ui']['reserve_submit'] ?? '') ?>"></label>
              <label>Event submit CTA<input type="text" name="ui_event_submit" value="<?= htmlspecialchars($langContent['ui']['event_submit'] ?? '') ?>"></label>
            </div>
            <div class="two-col">
              <label>Product submit CTA<input type="text" name="ui_product_submit" value="<?= htmlspecialchars($langContent['ui']['product_submit'] ?? '') ?>"></label>
              <label>Send inquiry CTA<input type="text" name="ui_send_inquiry" value="<?= htmlspecialchars($langContent['ui']['send_inquiry'] ?? '') ?>"></label>
            </div>
            <div class="two-col">
              <label>Cancel CTA<input type="text" name="ui_cancel" value="<?= htmlspecialchars($langContent['ui']['cancel'] ?? '') ?>"></label>
              <label>Default message placeholder<input type="text" name="ui_field_message_placeholder" value="<?= htmlspecialchars($langContent['ui']['field_message_placeholder'] ?? '') ?>"></label>
            </div>
            <div class="two-col">
              <label>Class placeholder<input type="text" name="ui_field_message_placeholder_class" value="<?= htmlspecialchars($langContent['ui']['field_message_placeholder_class'] ?? '') ?>"></label>
              <label>Event placeholder<input type="text" name="ui_field_message_placeholder_event" value="<?= htmlspecialchars($langContent['ui']['field_message_placeholder_event'] ?? '') ?>"></label>
            </div>
            <div class="two-col">
              <label>Product placeholder<input type="text" name="ui_field_message_placeholder_product" value="<?= htmlspecialchars($langContent['ui']['field_message_placeholder_product'] ?? '') ?>"></label>
              <label>Default success message<input type="text" name="ui_success" value="<?= htmlspecialchars($langContent['ui']['success'] ?? '') ?>"></label>
            </div>
            <div class="two-col">
              <label>Class success message<input type="text" name="ui_success_class" value="<?= htmlspecialchars($langContent['ui']['success_class'] ?? '') ?>"></label>
              <label>Event success message<input type="text" name="ui_success_event" value="<?= htmlspecialchars($langContent['ui']['success_event'] ?? '') ?>"></label>
            </div>
            <div class="two-col">
              <label>Product success message<input type="text" name="ui_success_product" value="<?= htmlspecialchars($langContent['ui']['success_product'] ?? '') ?>"></label>
              <label>Invalid email message<input type="text" name="ui_error_invalid_email" value="<?= htmlspecialchars($langContent['ui']['error_invalid_email'] ?? '') ?>"></label>
            </div>
            <div class="two-col">
              <label>Invalid phone message<input type="text" name="ui_error_invalid_phone" value="<?= htmlspecialchars($langContent['ui']['error_invalid_phone'] ?? '') ?>"></label>
            </div>
            <button class="primary" type="submit">Save Interface Copy</button>
          </form>
        </section>
        <?php endif; ?>

        <?php if ($selectedAdminSection === 'classes' && $selectedMode === 'list'): ?>
        <section class="panel">
          <h2><?= htmlspecialchars(sb_admin_text($selectedLang, 'Classes')) ?></h2>
          <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Manage classes as individual records. Open one class at a time for editing instead of changing the full schedule in one long form.')) ?></p>
          <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Class content is language-specific. Preview the same language on the public site after saving.')) ?></p>
          <div class="actions">
            <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['token' => $token, 'lang' => $selectedLang, 'section' => 'classes', 'mode' => 'create'])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Add class')) ?></a>
          </div>
          <table>
            <thead>
              <tr>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Title')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Schedule summary')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Primary location')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Price')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Actions')) ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($classItems as $class): ?>
                <?php
                  $classId = (string) ($class['id'] ?? '');
                  $editHref = sb_admin_query([
                      'token' => $token,
                      'lang' => $selectedLang,
                      'section' => 'classes',
                      'mode' => 'edit',
                      'item_id' => $classId,
                  ]);
                  $reservationsHref = sb_admin_query([
                      'token' => $token,
                      'lang' => $selectedLang,
                      'section' => 'bookings',
                      'class_filter' => $classId,
                      'time_scope' => $selectedTimeScope,
                  ]) . '#class-reservations';
                ?>
                <tr>
                  <td><strong><?= htmlspecialchars((string) ($class['title'] ?? 'Untitled class')) ?></strong></td>
                  <td><?= htmlspecialchars(sb_admin_reservation_class_meta([$class], $selectedLang)[$classId] ?? '') ?></td>
                  <td><?= htmlspecialchars((string) ((shine_bright_primary_class_schedule($class)['location'] ?? ''))) ?></td>
                  <td><?= htmlspecialchars((string) shine_bright_price_label($class)) ?></td>
                  <td>
                    <div class="actions">
                      <a class="inline-link" href="<?= htmlspecialchars($editHref) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Edit')) ?></a>
                      <a class="inline-link" href="<?= htmlspecialchars(sb_admin_public_item_path('classes', $classId, $selectedLang)) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Open class page')) ?></a>
                      <a class="inline-link" href="<?= htmlspecialchars($reservationsHref) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Class bookings')) ?></a>
                      <form method="post" onsubmit="return confirm('Delete this class?');">
                        <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
                        <input type="hidden" name="action" value="delete_item">
                        <input type="hidden" name="section" value="classes">
                        <input type="hidden" name="item_id" value="<?= htmlspecialchars($classId) ?>">
                        <button class="danger" type="submit">Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </section>
        <?php endif; ?>

        <?php if ($selectedAdminSection === 'classes' && in_array($selectedMode, ['create', 'edit'], true)): ?>
        <section class="panel">
          <?php
            $classEditorItem = $selectedMode === 'create' ? null : $selectedClassItem;
            $classForm = sb_admin_class_editor_defaults($classEditorItem);
          ?>
          <?= sb_admin_breadcrumbs($token, $selectedLang, 'classes', $selectedMode, 'Classes', (string) ($classEditorItem['title'] ?? $classEditorItem['id'] ?? '')) ?>
          <h2><?= htmlspecialchars(sb_admin_text($selectedLang, $selectedMode === 'create' ? 'Add Class' : 'Edit Class')) ?></h2>
          <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Class content is language-specific. Preview the same language on the public site after saving.')) ?></p>
            <form method="post" enctype="multipart/form-data">
              <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
              <input type="hidden" name="action" value="save_item">
              <input type="hidden" name="section" value="classes">
              <input type="hidden" name="original_item_id" value="<?= htmlspecialchars((string) ($classEditorItem['id'] ?? '')) ?>">
              <?php foreach (sb_admin_item_fields('classes', $classForm) as $field): ?>
                <?php if ($field === 'schedules') { continue; } ?>
                <label>
                  <?= htmlspecialchars(sb_admin_field_label($selectedLang, $field)) ?>
                  <?php if ($field === 'description'): ?>
                    <textarea name="field_<?= htmlspecialchars($field) ?>"><?= htmlspecialchars((string) ($classForm[$field] ?? '')) ?></textarea>
                  <?php else: ?>
                    <input
                      type="<?= htmlspecialchars(sb_admin_input_type($field)) ?>"
                      name="field_<?= htmlspecialchars($field) ?>"
                      value="<?= htmlspecialchars((string) ($classForm[$field] ?? '')) ?>"
                      <?= $field === 'price_eur' ? 'step="0.01" min="0"' : '' ?>
                    >
                  <?php endif; ?>
                </label>
              <?php endforeach; ?>
              <label><?= htmlspecialchars($selectedLang === 'en' ? 'Upload class image' : 'Качи снимка на класа') ?><input type="file" name="class_image_file" accept="image/jpeg,image/png,image/webp"></label>
              <?= sb_admin_media_preview($classForm['image_url'] ?? '') ?>
              <div class="schedule-editor" data-schedule-editor>
                <div class="schedule-editor-head">
                  <strong><?= htmlspecialchars(sb_admin_text($selectedLang, 'Schedules')) ?></strong>
                  <button class="inline-link" type="button" data-add-schedule><?= htmlspecialchars(sb_admin_text($selectedLang, 'Add schedule')) ?></button>
                </div>
                <div class="schedule-list" data-schedule-list>
                  <?php foreach ($classForm['schedules'] as $scheduleIndex => $schedule): ?>
                    <div class="schedule-row" data-schedule-row>
                      <input type="hidden" name="schedule_id[]" value="<?= htmlspecialchars((string) ($schedule['id'] ?? '')) ?>">
                      <div class="schedule-row-grid">
                        <label>
                          <?= htmlspecialchars(sb_admin_text($selectedLang, 'Weekday')) ?>
                          <select name="schedule_weekday[]">
                            <?= sb_admin_weekday_options($selectedLang, (string) ($schedule['weekday'] ?? '')) ?>
                          </select>
                        </label>
                        <label>
                          <?= htmlspecialchars(sb_admin_text($selectedLang, 'Location')) ?>
                          <input type="text" name="schedule_location[]" value="<?= htmlspecialchars((string) ($schedule['location'] ?? '')) ?>">
                        </label>
                      </div>
                      <div class="schedule-row-grid">
                        <label>
                          <?= htmlspecialchars(sb_admin_text($selectedLang, 'Start time')) ?>
                          <input type="time" name="schedule_start_time[]" value="<?= htmlspecialchars((string) ($schedule['start_time'] ?? '')) ?>">
                        </label>
                        <label>
                          <?= htmlspecialchars(sb_admin_text($selectedLang, 'End time')) ?>
                          <input type="time" name="schedule_end_time[]" value="<?= htmlspecialchars((string) ($schedule['end_time'] ?? '')) ?>">
                        </label>
                      </div>
                      <label>
                        <?= htmlspecialchars(sb_admin_text($selectedLang, 'Maps URL')) ?>
                        <input type="url" name="schedule_maps_url[]" value="<?= htmlspecialchars((string) ($schedule['maps_url'] ?? '')) ?>">
                      </label>
                      <button class="danger" type="button" data-remove-schedule><?= htmlspecialchars(sb_admin_text($selectedLang, 'Remove schedule')) ?></button>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="actions">
                <button class="primary" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Save Class')) ?></button>
                <?php if (($classEditorItem['id'] ?? '') !== ''): ?>
                  <a class="inline-link" href="<?= htmlspecialchars(sb_admin_public_item_path('classes', (string) ($classEditorItem['id'] ?? ''), $selectedLang)) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Open class page')) ?></a>
                <?php endif; ?>
                <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['token' => $token, 'lang' => $selectedLang, 'section' => 'classes'])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Back to list')) ?></a>
              </div>
            </form>
        </section>
        <?php endif; ?>

        <?php if ($selectedAdminSection === 'events' && $selectedMode === 'list'): ?>
        <section class="panel">
          <h2><?= htmlspecialchars(sb_admin_text($selectedLang, 'Events')) ?></h2>
          <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Manage events as individual records. Open one event at a time for editing instead of changing the whole event list in one long form.')) ?></p>
          <div class="actions">
            <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['token' => $token, 'lang' => $selectedLang, 'section' => 'events', 'mode' => 'create'])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Add event')) ?></a>
          </div>
          <table>
            <thead>
              <tr>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Title')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'When')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Location')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Price')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Actions')) ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($eventItems as $event): ?>
                <?php
                  $eventId = (string) ($event['id'] ?? '');
                  $editHref = sb_admin_query([
                      'token' => $token,
                      'lang' => $selectedLang,
                      'section' => 'events',
                      'mode' => 'edit',
                      'item_id' => $eventId,
                  ]);
                ?>
                <tr>
                  <td><strong><?= htmlspecialchars((string) ($event['title'] ?? 'Untitled event')) ?></strong></td>
                  <td><?= htmlspecialchars(sb_admin_reservation_class_meta([$event], $selectedLang)[$eventId] ?? '') ?></td>
                  <td><?= htmlspecialchars((string) ($event['location'] ?? '')) ?></td>
                  <td><?= htmlspecialchars((string) shine_bright_price_label($event)) ?></td>
                  <td>
                    <div class="actions">
                      <a class="inline-link" href="<?= htmlspecialchars($editHref) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Edit')) ?></a>
                      <form method="post" onsubmit="return confirm('Delete this event?');">
                        <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
                        <input type="hidden" name="action" value="delete_item">
                        <input type="hidden" name="section" value="events">
                        <input type="hidden" name="item_id" value="<?= htmlspecialchars($eventId) ?>">
                        <button class="danger" type="submit">Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </section>
        <?php endif; ?>

        <?php if ($selectedAdminSection === 'events' && in_array($selectedMode, ['create', 'edit'], true)): ?>
        <section class="panel">
          <?php
            $eventEditorItem = $selectedMode === 'create' ? null : $selectedEventItem;
            $eventForm = sb_admin_event_editor_defaults($eventEditorItem);
          ?>
          <?= sb_admin_breadcrumbs($token, $selectedLang, 'events', $selectedMode, 'Events', (string) ($eventEditorItem['title'] ?? $eventEditorItem['id'] ?? '')) ?>
          <h2><?= htmlspecialchars(sb_admin_text($selectedLang, $selectedMode === 'create' ? 'Add Event' : 'Edit Event')) ?></h2>
            <form method="post">
              <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
              <input type="hidden" name="action" value="save_item">
              <input type="hidden" name="section" value="events">
              <input type="hidden" name="original_item_id" value="<?= htmlspecialchars((string) ($eventEditorItem['id'] ?? '')) ?>">
              <div class="two-col">
                <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Calculated duration')) ?><input type="text" data-duration-output value="<?= htmlspecialchars(sb_admin_duration_preview($eventForm)) ?>" readonly></label>
              </div>
              <?php foreach (sb_admin_item_fields('events', $eventForm) as $field): ?>
                <label>
                  <?= htmlspecialchars(sb_admin_field_label($selectedLang, $field)) ?>
                  <?php if ($field === 'description'): ?>
                    <textarea name="field_<?= htmlspecialchars($field) ?>"><?= htmlspecialchars((string) ($eventForm[$field] ?? '')) ?></textarea>
                  <?php else: ?>
                    <input
                      type="<?= htmlspecialchars(sb_admin_input_type($field)) ?>"
                      name="field_<?= htmlspecialchars($field) ?>"
                      value="<?= htmlspecialchars((string) ($eventForm[$field] ?? '')) ?>"
                      <?= $field === 'price_eur' ? 'step="0.01" min="0"' : '' ?>
                      <?= in_array($field, ['start_at', 'end_at'], true) ? 'data-datetime-field' : '' ?>
                    >
                  <?php endif; ?>
                </label>
              <?php endforeach; ?>
              <div class="actions">
                <button class="primary" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Save Event')) ?></button>
                <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['token' => $token, 'lang' => $selectedLang, 'section' => 'events'])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Back to list')) ?></a>
              </div>
            </form>
        </section>
        <?php endif; ?>

        <?php if ($selectedAdminSection === 'products' && $selectedMode === 'list'): ?>
        <section class="panel">
          <h2><?= htmlspecialchars(sb_admin_text($selectedLang, 'Products')) ?></h2>
          <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Manage products as individual records. Open one product at a time for editing instead of working through the full product list in one long form.')) ?></p>
          <div class="actions">
            <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['token' => $token, 'lang' => $selectedLang, 'section' => 'products', 'mode' => 'create'])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Add product')) ?></a>
          </div>
          <table>
            <thead>
              <tr>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Product')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Category')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Detail')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Short description')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Price')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Actions')) ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($productItems as $product): ?>
                <?php
                  $productId = (string) ($product['id'] ?? '');
                  $editHref = sb_admin_query([
                      'token' => $token,
                      'lang' => $selectedLang,
                      'section' => 'products',
                      'mode' => 'edit',
                      'item_id' => $productId,
                  ]);
                ?>
                <tr>
                  <td>
                    <strong><?= htmlspecialchars((string) ($product['title'] ?? 'Untitled product')) ?></strong>
                    <?php if (($product['image_url'] ?? '') !== ''): ?>
                      <div class="panel-note"><?= htmlspecialchars((string) ($product['image_url'] ?? '')) ?></div>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars((string) ($product['category'] ?? '')) ?></td>
                  <td><?= htmlspecialchars((string) ($product['detail'] ?? '')) ?></td>
                  <td><?= htmlspecialchars((string) (($product['short_description'] ?? '') !== '' ? $product['short_description'] : $product['description'] ?? '')) ?></td>
                  <td><?= htmlspecialchars((string) shine_bright_price_label($product)) ?></td>
                  <td>
                    <div class="actions">
                      <a class="inline-link" href="<?= htmlspecialchars($editHref) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Edit')) ?></a>
                      <form method="post" onsubmit="return confirm('Delete this product?');">
                        <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
                        <input type="hidden" name="action" value="delete_item">
                        <input type="hidden" name="section" value="products">
                        <input type="hidden" name="item_id" value="<?= htmlspecialchars($productId) ?>">
                        <button class="danger" type="submit">Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </section>
        <?php endif; ?>

        <?php if ($selectedAdminSection === 'products' && in_array($selectedMode, ['create', 'edit'], true)): ?>
        <section class="panel">
          <?php
            $productEditorItem = $selectedMode === 'create' ? null : $selectedProductItem;
            $productForm = sb_admin_product_editor_defaults($productEditorItem);
          ?>
          <?= sb_admin_breadcrumbs($token, $selectedLang, 'products', $selectedMode, 'Products', (string) ($productEditorItem['title'] ?? $productEditorItem['id'] ?? '')) ?>
          <h2><?= htmlspecialchars(sb_admin_text($selectedLang, $selectedMode === 'create' ? 'Add Product' : 'Edit Product')) ?></h2>
            <form method="post" enctype="multipart/form-data">
              <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
              <input type="hidden" name="action" value="save_item">
              <input type="hidden" name="section" value="products">
              <input type="hidden" name="original_item_id" value="<?= htmlspecialchars((string) ($productEditorItem['id'] ?? '')) ?>">
              <?php foreach (sb_admin_item_fields('products', $productForm) as $field): ?>
                <label>
                  <?= htmlspecialchars(sb_admin_field_label($selectedLang, $field)) ?>
                  <?php if (in_array($field, ['short_description', 'description'], true)): ?>
                    <textarea name="field_<?= htmlspecialchars($field) ?>"><?= htmlspecialchars((string) ($productForm[$field] ?? '')) ?></textarea>
                  <?php else: ?>
                    <input
                      type="<?= htmlspecialchars(sb_admin_input_type($field)) ?>"
                      name="field_<?= htmlspecialchars($field) ?>"
                      value="<?= htmlspecialchars((string) ($productForm[$field] ?? '')) ?>"
                      <?= $field === 'price_eur' ? 'step="0.01" min="0"' : '' ?>
                    >
                  <?php endif; ?>
                </label>
              <?php endforeach; ?>
              <fieldset>
                <legend><?= htmlspecialchars(sb_admin_text($selectedLang, 'Product image framing')) ?></legend>
                <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Adjust how the product image sits in its card and detail view.')) ?></p>
                <div id="product-image-preview" class="product-image-preview" style="<?= ($productForm['image_url'] ?? '') !== '' ? 'background-image:url(\'' . htmlspecialchars($productForm['image_url'] ?? '') . '\'); background-position:' . htmlspecialchars((string) ($productForm['image_focus_x'] ?? '50')) . '% ' . htmlspecialchars((string) ($productForm['image_focus_y'] ?? '50')) . '%; background-size:' . htmlspecialchars((string) ($productForm['image_zoom'] ?? '100')) . '% auto;' : '' ?>">
                  <?php if (($productForm['image_url'] ?? '') === ''): ?>
                    <span class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Upload an image to see live preview.')) ?></span>
                  <?php endif; ?>
                </div>
                <div class="two-col">
                  <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Image focus X')) ?>
                    <span class="range-wrap">
                      <input type="range" class="image-focus-slider" data-target="field_image_focus_x" min="0" max="100" step="1" value="<?= htmlspecialchars((string) ($productForm['image_focus_x'] ?? '50')) ?>">
                      <input type="number" id="field_image_focus_x" min="0" max="100" step="1" name="field_image_focus_x" value="<?= htmlspecialchars((string) ($productForm['image_focus_x'] ?? '50')) ?>" class="image-focus-number">
                    </span>
                  </label>
                  <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Image focus Y')) ?>
                    <span class="range-wrap">
                      <input type="range" class="image-focus-slider" data-target="field_image_focus_y" min="0" max="100" step="1" value="<?= htmlspecialchars((string) ($productForm['image_focus_y'] ?? '50')) ?>">
                      <input type="number" id="field_image_focus_y" min="0" max="100" step="1" name="field_image_focus_y" value="<?= htmlspecialchars((string) ($productForm['image_focus_y'] ?? '50')) ?>" class="image-focus-number">
                    </span>
                  </label>
                </div>
                <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Image zoom')) ?>
                  <span class="range-wrap">
                    <input type="range" class="image-zoom-slider" data-target="field_image_zoom" min="80" max="200" step="1" value="<?= htmlspecialchars((string) ($productForm['image_zoom'] ?? '100')) ?>">
                    <input type="number" id="field_image_zoom" min="80" max="200" step="1" name="field_image_zoom" value="<?= htmlspecialchars((string) ($productForm['image_zoom'] ?? '100')) ?>" class="image-zoom-number">
                  </span>
                </label>
              </fieldset>
              <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Upload product image')) ?><input type="file" name="product_image_file" accept="image/jpeg,image/png,image/webp"></label>
              <?= sb_admin_media_preview($productForm['image_url'] ?? '') ?>
              <div class="actions">
                <button class="primary" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Save Product')) ?></button>
                <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['token' => $token, 'lang' => $selectedLang, 'section' => 'products'])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Back to list')) ?></a>
              </div>
            </form>
        </section>
        <?php endif; ?>

        <?php if ($selectedAdminSection === 'clients' && $selectedMode === 'list'): ?>
        <section class="panel">
          <h2><?= htmlspecialchars(sb_admin_text($selectedLang, 'Students')) ?></h2>
          <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Keep one record per person so visit cards and future attendance notes stay attached to a stable student record.')) ?></p>
          <div class="actions">
            <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['token' => $token, 'lang' => $selectedLang, 'section' => 'clients', 'mode' => 'create'])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Add student')) ?></a>
          </div>
          <table>
            <thead>
              <tr>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Student')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Phone')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Email')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Visit cards')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Access status')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Actions')) ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($clients as $client): ?>
                <?php
                  $clientId = (string) ($client['id'] ?? '');
                  $isRuntimeOnly = !empty($client['_is_runtime_only']);
                  $editHref = sb_admin_query([
                      'token' => $token,
                      'lang' => $selectedLang,
                      'section' => 'clients',
                      'mode' => 'edit',
                      'item_id' => $clientId,
                  ]);
                ?>
                <tr>
                  <td><strong><?= htmlspecialchars((string) ($client['name'] ?? 'Unnamed student')) ?></strong></td>
                  <td><?= htmlspecialchars((string) ($client['phone'] ?? '')) ?></td>
                  <td><?= htmlspecialchars((string) ($client['email'] ?? '')) ?></td>
                  <td><?= shine_bright_client_active_pack_count($visitPacks, $clientId) ?>/<?= shine_bright_client_pack_count($visitPacks, $clientId) ?></td>
                  <td><?= htmlspecialchars(sb_admin_text($selectedLang, ucfirst((string) ($client['account_status'] ?? 'invited')))) ?></td>
                  <td>
                    <div class="actions">
                      <a class="inline-link" href="<?= htmlspecialchars($editHref) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Edit')) ?></a>
                      <?php if (!$isRuntimeOnly && ($client['email'] ?? '') !== '' && (string) ($client['account_status'] ?? 'invited') !== 'active'): ?>
                        <form method="post">
                          <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
                          <input type="hidden" name="action" value="send_student_invitation">
                          <input type="hidden" name="student_id" value="<?= htmlspecialchars($clientId) ?>">
                          <button class="secondary" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Send activation email')) ?></button>
                        </form>
                      <?php endif; ?>
                      <?php if (!$isRuntimeOnly): ?>
                        <form method="post" onsubmit="return confirm('Delete this client?');">
                          <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
                          <input type="hidden" name="action" value="delete_client">
                          <input type="hidden" name="client_id" value="<?= htmlspecialchars($clientId) ?>">
                          <button class="danger" type="submit">Delete</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </section>
        <?php endif; ?>

        <?php if ($selectedAdminSection === 'contacts'): ?>
        <section class="panel">
          <h2><?= htmlspecialchars(sb_admin_text($selectedLang, 'Contacts')) ?></h2>
          <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Contacts are people known to the platform through reservations or inquiries, but who are not yet students. Convert them explicitly when Maria wants to bring them into the managed student flow.')) ?></p>
          <?php if ($contactLeads === []): ?>
            <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'No contacts yet.')) ?></p>
          <?php else: ?>
          <table>
            <thead>
              <tr>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Name')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Phone')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Email')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Source')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Lifecycle')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Actions')) ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($contactLeads as $lead): ?>
                <tr>
                  <td><strong><?= htmlspecialchars((string) ($lead['name'] ?? '')) ?></strong></td>
                  <td><?= htmlspecialchars((string) ($lead['phone'] ?? '')) ?></td>
                  <td><?= htmlspecialchars((string) ($lead['email'] ?? '')) ?></td>
                  <td><?= htmlspecialchars(sb_admin_text($selectedLang, ucfirst((string) ($lead['source'] ?? 'contact')))) ?></td>
                  <td><?= htmlspecialchars(sb_admin_text($selectedLang, sb_admin_contact_status_label($lead))) ?></td>
                  <td>
                    <div class="actions">
                      <?php if (($lead['email'] ?? '') !== '' && filter_var((string) ($lead['email'] ?? ''), FILTER_VALIDATE_EMAIL)): ?>
                        <form method="post">
                          <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
                          <input type="hidden" name="action" value="convert_contact">
                          <input type="hidden" name="lead_id" value="<?= htmlspecialchars((string) ($lead['id'] ?? '')) ?>">
                          <button class="secondary" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Convert to student')) ?></button>
                        </form>
                      <?php else: ?>
                        <span class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Enter a valid email address.')) ?></span>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if ($selectedAdminSection === 'clients' && in_array($selectedMode, ['create', 'edit'], true)): ?>
        <section class="panel">
          <?php
            $clientEditorItem = $selectedMode === 'create' ? null : $selectedClientItem;
            $clientForm = sb_admin_client_editor_defaults($clientEditorItem);
          ?>
          <?= sb_admin_breadcrumbs($token, $selectedLang, 'clients', $selectedMode, 'Students', (string) ($clientEditorItem['name'] ?? $clientEditorItem['id'] ?? '')) ?>
          <h2><?= htmlspecialchars(sb_admin_text($selectedLang, $selectedMode === 'create' ? 'Add Student' : 'Edit Student')) ?></h2>
          <form method="post">
            <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
            <input type="hidden" name="action" value="save_client">
            <input type="hidden" name="original_client_id" value="<?= htmlspecialchars((string) ($clientEditorItem['id'] ?? '')) ?>">
            <div class="two-col">
              <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Name')) ?><input type="text" name="client_name" value="<?= htmlspecialchars((string) ($clientForm['name'] ?? '')) ?>"></label>
              <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Phone')) ?><input type="text" name="client_phone" value="<?= htmlspecialchars((string) ($clientForm['phone'] ?? '')) ?>"></label>
            </div>
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Email')) ?><input type="email" name="client_email" value="<?= htmlspecialchars((string) ($clientForm['email'] ?? '')) ?>"></label>
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Notes')) ?><textarea name="client_notes"><?= htmlspecialchars((string) ($clientForm['notes'] ?? '')) ?></textarea></label>
            <div class="actions">
              <button class="primary" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Save Student')) ?></button>
              <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['token' => $token, 'lang' => $selectedLang, 'section' => 'clients'])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Back to list')) ?></a>
            </div>
          </form>
          <?php if ($selectedMode === 'edit' && ($clientForm['email'] ?? '') !== '' && (string) ($clientForm['account_status'] ?? 'invited') !== 'active'): ?>
            <form method="post" class="actions" style="margin-top:12px;">
              <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
              <input type="hidden" name="action" value="send_student_invitation">
              <input type="hidden" name="student_id" value="<?= htmlspecialchars((string) ($clientEditorItem['id'] ?? '')) ?>">
              <button class="secondary" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Send activation email')) ?></button>
            </form>
          <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if ($selectedAdminSection === 'visit-packs' && $selectedMode === 'list'): ?>
        <section class="panel">
          <h2><?= htmlspecialchars(sb_admin_text($selectedLang, 'Visit Cards')) ?></h2>
          <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Issue prepaid visit cards, watch remaining visits, and quickly spot low, completed, or expired cards.')) ?></p>
          <div class="actions">
            <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['token' => $token, 'lang' => $selectedLang, 'section' => 'visit-packs', 'mode' => 'create', 'visit_page' => $selectedVisitPage, 'visit_per_page' => $selectedVisitPerPage])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Add visit card')) ?></a>
          </div>
          <form class="actions" method="get" style="margin-bottom:14px;">
            <input type="hidden" name="lang" value="<?= htmlspecialchars($selectedLang) ?>">
            <input type="hidden" name="section" value="visit-packs">
            <label>
              <?= htmlspecialchars(sb_admin_text($selectedLang, 'Show')) ?>
              <select name="visit_per_page">
                <option value="10"<?= $selectedVisitPerPage === '10' ? ' selected' : '' ?>>10</option>
                <option value="25"<?= $selectedVisitPerPage === '25' ? ' selected' : '' ?>>25</option>
                <option value="50"<?= $selectedVisitPerPage === '50' ? ' selected' : '' ?>>50</option>
                <option value="all"<?= $selectedVisitPerPage === 'all' ? ' selected' : '' ?>><?= htmlspecialchars(sb_admin_text($selectedLang, 'All')) ?></option>
              </select>
            </label>
            <input type="hidden" name="visit_page" value="1">
            <button class="secondary" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Show')) ?></button>
            <span class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Showing visit cards')) ?>: <?= $visitPackRangeStart ?>-<?= $visitPackRangeEnd ?> <?= htmlspecialchars(sb_admin_text($selectedLang, 'of total visit cards')) ?> <?= $visitPackTotalCount ?></span>
          </form>
          <table>
            <thead>
              <tr>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Student')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Valid for')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Card')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Record ID')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Visits')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Expiry')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Status')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Actions')) ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($paginatedVisitPacks as $pack): ?>
                <?php
                  $packId = (string) ($pack['id'] ?? '');
                  $editHref = sb_admin_query([
                      'token' => $token,
                      'lang' => $selectedLang,
                      'section' => 'visit-packs',
                      'mode' => 'edit',
                      'item_id' => $packId,
                      'visit_page' => $selectedVisitPage,
                      'visit_per_page' => $selectedVisitPerPage,
                  ]);
                ?>
                <tr>
                  <td><?= htmlspecialchars(sb_admin_find_client_name($clients, (string) ($pack['client_id'] ?? ''))) ?></td>
                  <td><?= htmlspecialchars(sb_admin_find_class_titles($classItems, shine_bright_visit_pack_allowed_class_ids($pack), $selectedLang)) ?></td>
                  <td>
                    <strong><?= htmlspecialchars((string) ($pack['title'] ?? 'Untitled visit card')) ?></strong>
                    <div class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Created at')) ?>: <?= htmlspecialchars(sb_admin_format_admin_datetime((string) ($pack['created_at'] ?? ''))) ?></div>
                    <div class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Updated at')) ?>: <?= htmlspecialchars(sb_admin_format_admin_datetime((string) ($pack['updated_at'] ?? ''))) ?></div>
                  </td>
                  <td><code><?= htmlspecialchars($packId) ?></code></td>
                  <td><?= htmlspecialchars(shine_bright_visit_pack_usage_summary($pack)) ?></td>
                  <td><?= htmlspecialchars(sb_admin_format_pack_expiry($pack)) ?></td>
                  <td><?= sb_admin_visit_pack_status_badge($selectedLang, $pack) ?></td>
                  <td>
                    <div class="actions">
                      <a class="inline-link" href="<?= htmlspecialchars($editHref) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Edit')) ?></a>
                      <form method="post" onsubmit="return confirm('Use one visit from this card?');">
                        <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
                        <input type="hidden" name="action" value="use_visit_pack">
                        <input type="hidden" name="pack_id" value="<?= htmlspecialchars($packId) ?>">
                        <button class="secondary" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Use 1 visit')) ?></button>
                      </form>
                      <form method="post" onsubmit="return confirm('Delete this visit card?');">
                        <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
                        <input type="hidden" name="action" value="delete_visit_pack">
                        <input type="hidden" name="pack_id" value="<?= htmlspecialchars($packId) ?>">
                        <button class="danger" type="submit">Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php if ($selectedVisitPerPage !== 'all' && $visitPackPageCount > 1): ?>
            <div class="actions" style="margin-top:16px;">
              <?php if ($selectedVisitPageNumber > 1): ?>
                <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['lang' => $selectedLang, 'section' => 'visit-packs', 'visit_page' => (string) ($selectedVisitPageNumber - 1), 'visit_per_page' => $selectedVisitPerPage])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Previous')) ?></a>
              <?php endif; ?>
              <span class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Page')) ?> <?= $selectedVisitPageNumber ?> / <?= $visitPackPageCount ?></span>
              <?php if ($selectedVisitPageNumber < $visitPackPageCount): ?>
                <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['lang' => $selectedLang, 'section' => 'visit-packs', 'visit_page' => (string) ($selectedVisitPageNumber + 1), 'visit_per_page' => $selectedVisitPerPage])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Next')) ?></a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if ($selectedAdminSection === 'visit-packs' && in_array($selectedMode, ['create', 'edit'], true)): ?>
        <section class="panel">
          <?php
            $packEditorItem = $selectedMode === 'create' ? null : $selectedVisitPackItem;
            $packForm = sb_admin_visit_pack_editor_defaults($packEditorItem);
            $packUsageEvents = $packEditorItem ? array_reverse(shine_bright_pack_usage_events($visitUsage, (string) ($packEditorItem['id'] ?? ''))) : [];
            $packAllowedClassIds = shine_bright_visit_pack_allowed_class_ids($packForm);
          ?>
          <?= sb_admin_breadcrumbs($token, $selectedLang, 'visit-packs', $selectedMode, 'Visit Cards', (string) ($packEditorItem['title'] ?? $packEditorItem['id'] ?? '')) ?>
          <h2><?= htmlspecialchars(sb_admin_text($selectedLang, $selectedMode === 'create' ? 'Add Visit Card' : 'Edit Visit Card')) ?></h2>
          <form method="post">
            <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
            <input type="hidden" name="action" value="save_visit_pack">
            <input type="hidden" name="original_pack_id" value="<?= htmlspecialchars((string) ($packEditorItem['id'] ?? '')) ?>">
            <?php if ($selectedMode === 'edit' && $packEditorItem): ?>
            <div class="two-col">
              <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Record ID')) ?><input type="text" value="<?= htmlspecialchars((string) ($packEditorItem['id'] ?? '')) ?>" readonly></label>
              <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Created at')) ?><input type="text" value="<?= htmlspecialchars(sb_admin_format_admin_datetime((string) ($packEditorItem['created_at'] ?? ''))) ?>" readonly></label>
            </div>
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Updated at')) ?><input type="text" value="<?= htmlspecialchars(sb_admin_format_admin_datetime((string) ($packEditorItem['updated_at'] ?? ''))) ?>" readonly></label>
            <?php endif; ?>
            <div class="two-col">
              <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Student')) ?>
                <select name="pack_client_id">
                  <option value=""><?= htmlspecialchars(sb_admin_text($selectedLang, 'Choose student')) ?></option>
                  <?php foreach ($clients as $client): ?>
                    <?php $clientId = (string) ($client['id'] ?? ''); ?>
                    <option value="<?= htmlspecialchars($clientId) ?>"<?= $clientId === (string) ($packForm['client_id'] ?? '') ? ' selected' : '' ?>><?= htmlspecialchars((string) ($client['name'] ?? $clientId)) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Title')) ?><input type="text" name="pack_title" value="<?= htmlspecialchars((string) ($packForm['title'] ?? '')) ?>"></label>
            </div>
            <fieldset class="class-scope-group">
              <legend><?= htmlspecialchars(sb_admin_text($selectedLang, 'Valid for classes')) ?></legend>
              <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Choose one or more classes, or leave everything unchecked for all classes.')) ?></p>
              <div class="class-scope-grid">
                <?php $packClassIds = shine_bright_visit_pack_allowed_class_ids($packForm); ?>
                <?php foreach ($classItems as $class): ?>
                  <?php $classId = (string) ($class['id'] ?? ''); ?>
                  <label class="class-scope-option">
                    <input type="checkbox" name="pack_applies_to_class_ids[]" value="<?= htmlspecialchars($classId) ?>"<?= in_array($classId, $packClassIds, true) ? ' checked' : '' ?>>
                    <span><?= htmlspecialchars((string) ($class['title'] ?? $classId)) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </fieldset>
            <div class="two-col">
              <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Total visits')) ?><input type="number" min="1" step="1" name="pack_total_visits" value="<?= htmlspecialchars((string) ($packForm['total_visits'] ?? '0')) ?>"></label>
              <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Used visits')) ?><input type="number" min="0" step="1" name="pack_used_visits" value="<?= htmlspecialchars((string) ($packForm['used_visits'] ?? '0')) ?>"></label>
            </div>
            <div class="two-col">
              <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Starts on')) ?><input type="date" name="pack_starts_on" value="<?= htmlspecialchars((string) ($packForm['starts_on'] ?? '')) ?>"></label>
              <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Expires on')) ?><input type="date" name="pack_expires_on" value="<?= htmlspecialchars((string) ($packForm['expires_on'] ?? '')) ?>"></label>
            </div>
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Status')) ?>
              <select name="pack_status">
                <?= sb_admin_select_option('active', (string) ($packForm['status'] ?? 'active'), sb_admin_text($selectedLang, 'Active')) ?>
                <?= sb_admin_select_option('cancelled', (string) ($packForm['status'] ?? 'active'), sb_admin_text($selectedLang, 'Cancelled')) ?>
              </select>
            </label>
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Notes')) ?><textarea name="pack_notes"><?= htmlspecialchars((string) ($packForm['notes'] ?? '')) ?></textarea></label>
            <div class="actions">
              <button class="primary" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Save Visit Card')) ?></button>
              <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['token' => $token, 'lang' => $selectedLang, 'section' => 'visit-packs'])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Back to list')) ?></a>
            </div>
          </form>

          <?php if ($selectedMode === 'edit' && $packEditorItem): ?>
            <div class="section-stack">
              <section class="panel" style="margin-top:18px;">
                <h3><?= htmlspecialchars(sb_admin_text($selectedLang, 'Use 1 Visit')) ?></h3>
                <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Record one attendance from this visit card. Class and note are optional in v1.')) ?></p>
                <form method="post">
                  <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
                  <input type="hidden" name="action" value="use_visit_pack">
                  <input type="hidden" name="pack_id" value="<?= htmlspecialchars((string) ($packEditorItem['id'] ?? '')) ?>">
                  <div class="two-col">
                    <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Used on')) ?><input type="date" name="usage_used_on" value="<?= htmlspecialchars(gmdate('Y-m-d')) ?>"></label>
                    <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Class (optional)')) ?>
                      <select name="usage_class_id">
                        <option value=""><?= htmlspecialchars(sb_admin_text($selectedLang, 'Manual / no class linked')) ?></option>
                        <?php foreach ($classItems as $class): ?>
                          <?php $usageClassId = (string) ($class['id'] ?? ''); ?>
                          <?php if ($packAllowedClassIds !== [] && !in_array($usageClassId, $packAllowedClassIds, true)) { continue; } ?>
                          <option value="<?= htmlspecialchars($usageClassId) ?>"><?= htmlspecialchars((string) ($class['title'] ?? ($class['id'] ?? ''))) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </label>
                  </div>
                  <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Note')) ?><input type="text" name="usage_note" value="" placeholder="<?= htmlspecialchars(sb_admin_text($selectedLang, 'Optional attendance note')) ?>"></label>
                  <div class="actions">
                    <button class="secondary" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Use 1 visit')) ?></button>
                    <span class="panel-note"><?= htmlspecialchars(shine_bright_visit_pack_usage_summary($packEditorItem)) ?></span>
                    <?= sb_admin_visit_pack_status_badge($selectedLang, $packEditorItem) ?>
                  </div>
                </form>
              </section>

              <section class="panel" style="margin-top:18px;">
                <h3><?= htmlspecialchars(sb_admin_text($selectedLang, 'Usage History')) ?></h3>
                <?php if ($packUsageEvents === []): ?>
                  <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'No visits have been recorded yet.')) ?></p>
                <?php else: ?>
                  <div class="usage-list">
                    <?php foreach ($packUsageEvents as $event): ?>
                      <article class="usage-item">
                        <strong><?= htmlspecialchars((string) ($event['used_on'] ?? '')) ?></strong>
                        <div class="reservation-meta">
                          <div>Source: <?= htmlspecialchars((string) ($event['source'] ?? 'manual')) ?></div>
                          <?php if (($event['class_id'] ?? '') !== ''): ?>
                            <div>Class: <?= htmlspecialchars((string) (shine_bright_find_content_item($content, $selectedLang, 'classes', (string) $event['class_id'])['title'] ?? $event['class_id'])) ?></div>
                          <?php endif; ?>
                          <?php if (($event['note'] ?? '') !== ''): ?>
                            <div>Note: <?= htmlspecialchars((string) $event['note']) ?></div>
                          <?php endif; ?>
                        </div>
                      </article>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </section>
            </div>
          <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if ($selectedAdminSection === 'testimonials' && $selectedMode === 'list'): ?>
        <section class="panel">
          <h2><?= htmlspecialchars(sb_admin_text($selectedLang, 'Testimonials')) ?></h2>
          <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Manage testimonials as individual records. Open one testimonial at a time for editing.')) ?></p>
          <div class="actions">
            <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['token' => $token, 'lang' => $selectedLang, 'section' => 'testimonials', 'mode' => 'create'])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Add testimonial')) ?></a>
          </div>
          <table>
            <thead>
              <tr>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Name')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Quote')) ?></th>
                <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Actions')) ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($testimonialItems as $testimonial): ?>
                <?php
                  $testimonialId = (string) ($testimonial['id'] ?? '');
                  $editHref = sb_admin_query([
                      'token' => $token,
                      'lang' => $selectedLang,
                      'section' => 'testimonials',
                      'mode' => 'edit',
                      'item_id' => $testimonialId,
                  ]);
                ?>
                <tr>
                  <td><strong><?= htmlspecialchars((string) ($testimonial['name'] ?? '')) ?></strong></td>
                  <?php $quotePreview = (string) ($testimonial['quote'] ?? ''); ?>
                  <td><?= htmlspecialchars(strlen($quotePreview) > 120 ? substr($quotePreview, 0, 117) . '...' : $quotePreview) ?></td>
                  <td>
                    <div class="actions">
                      <a class="inline-link" href="<?= htmlspecialchars($editHref) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Edit')) ?></a>
                      <form method="post" onsubmit="return confirm('Delete this testimonial?');">
                        <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
                        <input type="hidden" name="action" value="delete_item">
                        <input type="hidden" name="section" value="testimonials">
                        <input type="hidden" name="item_id" value="<?= htmlspecialchars($testimonialId) ?>">
                        <button class="danger" type="submit">Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </section>
        <?php endif; ?>

        <?php if ($selectedAdminSection === 'testimonials' && in_array($selectedMode, ['create', 'edit'], true)): ?>
        <section class="panel">
          <?php
            $testimonialEditorItem = $selectedMode === 'create' ? null : $selectedTestimonialItem;
            $testimonialForm = sb_admin_testimonial_editor_defaults($testimonialEditorItem);
          ?>
          <?= sb_admin_breadcrumbs($token, $selectedLang, 'testimonials', $selectedMode, 'Testimonials', (string) ($testimonialEditorItem['name'] ?? $testimonialEditorItem['id'] ?? '')) ?>
          <h2><?= htmlspecialchars(sb_admin_text($selectedLang, $selectedMode === 'create' ? 'Add Testimonial' : 'Edit Testimonial')) ?></h2>
          <form method="post">
            <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
            <input type="hidden" name="action" value="save_item">
            <input type="hidden" name="section" value="testimonials">
            <input type="hidden" name="original_item_id" value="<?= htmlspecialchars((string) ($testimonialEditorItem['id'] ?? '')) ?>">
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Quote')) ?><textarea name="field_quote"><?= htmlspecialchars((string) ($testimonialForm['quote'] ?? '')) ?></textarea></label>
            <label><?= htmlspecialchars(sb_admin_text($selectedLang, 'Name')) ?><input type="text" name="field_name" value="<?= htmlspecialchars((string) ($testimonialForm['name'] ?? '')) ?>"></label>
            <div class="actions">
              <button class="primary" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Save Testimonial')) ?></button>
              <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['token' => $token, 'lang' => $selectedLang, 'section' => 'testimonials'])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Back to list')) ?></a>
            </div>
          </form>
        </section>
        <?php endif; ?>
      </div>

      <div>
        <?php if ($selectedAdminSection === 'classes' && $selectedMode === 'list'): ?>
        <section class="panel">
          <h2><?= htmlspecialchars(sb_admin_text($selectedLang, 'Class Sessions')) ?></h2>
          <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Use this as the main organizer view. Each class shows its booking totals and quick links to edit the class or review its class bookings.')) ?></p>
          <div class="actions" style="margin-bottom:14px;">
            <label>
              <input type="text" id="class-name-filter" placeholder="<?= htmlspecialchars(sb_admin_text($selectedLang, 'Search class by name...')) ?>" style="min-width:240px;">
            </label>
            <label>
              <select id="class-attendance-filter">
                <option value=""><?= htmlspecialchars(sb_admin_text($selectedLang, 'Any attendance')) ?></option>
                <option value="1"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Has bookings (1+)')) ?></option>
                <option value="5"><?= htmlspecialchars(sb_admin_text($selectedLang, '5+ total bookings')) ?></option>
                <option value="10"><?= htmlspecialchars(sb_admin_text($selectedLang, '10+ total bookings')) ?></option>
                <option value="20"><?= htmlspecialchars(sb_admin_text($selectedLang, '20+ total bookings')) ?></option>
              </select>
            </label>
            <span id="class-filter-count" class="panel-note" style="margin:0;"></span>
          </div>
          <div class="session-overview" id="class-cards-list">
            <?php foreach ($classItems as $index => $class): ?>
              <?php
                $classId = (string) ($class['id'] ?? '');
                $sessionCounts = sb_admin_class_reservation_counts($reservations, $classId);
                $editHref = sb_admin_query([
                    'token' => $token,
                    'lang' => $selectedLang,
                    'section' => 'classes',
                    'mode' => 'edit',
                    'item_id' => $classId,
                ]) . '#item-classes-' . ($classId !== '' ? $classId : (string) $index);
                $filterHref = sb_admin_query([
                    'token' => $token,
                    'lang' => $selectedLang,
                    'section' => 'bookings',
                    'class_filter' => $classId,
                    'time_scope' => $selectedTimeScope,
                ]) . '#class-reservations';
              ?>
              <article class="session-card" data-class-name="<?= htmlspecialchars(mb_strtolower((string) ($class['title'] ?? ''))) ?>" data-total-bookings="<?= (int) $sessionCounts['total'] ?>">
                <div class="session-top">
                  <div>
                    <h3><?= htmlspecialchars((string) ($class['title'] ?? 'Untitled class')) ?></h3>
                    <p><?= htmlspecialchars(sb_admin_reservation_class_meta([$class], $selectedLang)[$classId] ?? '') ?></p>
                  </div>
                  <strong><?= htmlspecialchars((string) shine_bright_price_label($class)) ?></strong>
                </div>
                <div class="session-stats">
                  <div><strong><?= $sessionCounts['total'] ?></strong><span><?= htmlspecialchars(sb_admin_text($selectedLang, 'Total')) ?></span></div>
                  <div><strong><?= $sessionCounts['new'] ?></strong><span><?= htmlspecialchars(sb_admin_text($selectedLang, 'New')) ?></span></div>
                  <div><strong><?= $sessionCounts['confirmed'] ?></strong><span><?= htmlspecialchars(sb_admin_text($selectedLang, 'Confirmed')) ?></span></div>
                  <div><strong><?= $sessionCounts['waitlisted'] ?></strong><span><?= htmlspecialchars(sb_admin_text($selectedLang, 'Waitlisted')) ?></span></div>
                  <div><strong><?= $sessionCounts['attended'] ?></strong><span><?= htmlspecialchars(sb_admin_text($selectedLang, 'Attended')) ?></span></div>
                </div>
                <div class="actions">
                  <a class="inline-link" href="<?= htmlspecialchars($editHref) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Edit class')) ?></a>
                  <a class="inline-link" href="<?= htmlspecialchars($filterHref) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'View class bookings')) ?></a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
        <?php endif; ?>

        <?php if (in_array($selectedAdminSection, ['dashboard', 'bookings'], true)): ?>
        <section class="panel">
          <h2><?= htmlspecialchars(sb_admin_text($selectedLang, 'Organizer view')) ?></h2>
          <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Next 7 days of class sessions with reservation counts so Maria can see what is coming up next.')) ?></p>
          <form class="actions" method="get" style="margin-bottom:14px;">
            <input type="hidden" name="lang" value="<?= htmlspecialchars($selectedLang) ?>">
            <input type="hidden" name="section" value="<?= htmlspecialchars($selectedAdminSection) ?>">
            <input type="hidden" name="time_scope" value="<?= htmlspecialchars($selectedTimeScope) ?>">
            <label>
              <?= htmlspecialchars(sb_admin_text($selectedLang, 'Apply class filter')) ?>
              <select name="class_filter">
                <option value=""><?= htmlspecialchars(sb_admin_text($selectedLang, 'All classes')) ?></option>
                <?php foreach ($classItems as $class): ?>
                  <?php $filterClassId = (string) ($class['id'] ?? ''); ?>
                  <option value="<?= htmlspecialchars($filterClassId) ?>"<?= $filterClassId === $selectedClassFilter ? ' selected' : '' ?>><?= htmlspecialchars((string) ($class['title'] ?? $filterClassId)) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <button class="secondary" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Apply class filter')) ?></button>
            <?php if ($selectedClassFilter !== ''): ?>
              <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query([
                  'lang' => $selectedLang,
                  'section' => $selectedAdminSection,
                  'time_scope' => $selectedTimeScope,
              ])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Show all class bookings')) ?></a>
            <?php endif; ?>
          </form>
          <div class="scope-switch" aria-label="<?= htmlspecialchars(sb_admin_text($selectedLang, 'Organizer view')) ?>">
            <?php foreach (['today' => 'Today', 'tomorrow' => 'Tomorrow', 'week' => 'Next 7 days'] as $scopeKey => $scopeLabel): ?>
              <a
                class="<?= $selectedTimeScope === $scopeKey ? 'active' : '' ?>"
                href="<?= htmlspecialchars(sb_admin_query([
                    'lang' => $selectedLang,
                    'section' => $selectedAdminSection,
                    'class_filter' => $selectedClassFilter,
                    'time_scope' => $scopeKey,
                ])) ?>"
              ><?= htmlspecialchars(sb_admin_text($selectedLang, $scopeLabel)) ?></a>
            <?php endforeach; ?>
          </div>
          <?php if ($upcomingAdminSessions === []): ?>
            <?php
              $emptyLabel = match ($selectedTimeScope) {
                  'today' => 'No sessions today.',
                  'tomorrow' => 'No sessions tomorrow.',
                  default => 'No sessions in the next 7 days.',
              };
            ?>
            <p><?= htmlspecialchars(sb_admin_text($selectedLang, $emptyLabel)) ?></p>
          <?php else: ?>
            <?php if ($selectedSessionView): ?>
              <?php
                $selectedSessionReservations = sb_admin_session_reservations($filteredReservations, $selectedSessionView);
                $selectedSessionCounts = sb_admin_reservation_status_counts($selectedSessionReservations);
              ?>
              <section class="panel" id="session-detail">
                <p class="eyebrow"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Session view')) ?></p>
                <div class="session-view-shell">
                  <div class="session-view-header">
                    <div>
                      <h3><?= htmlspecialchars((string) ($selectedSessionView['class_title'] ?? '')) ?></h3>
                      <p><?= htmlspecialchars((string) ($selectedSessionView['summary_label'] ?? '')) ?></p>
                      <?php if (($selectedSessionView['location'] ?? '') !== ''): ?>
                        <p><?= htmlspecialchars((string) ($selectedSessionView['location'] ?? '')) ?></p>
                      <?php endif; ?>
                    </div>
                    <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query([
                        'lang' => $selectedLang,
                        'section' => $selectedAdminSection,
                        'class_filter' => $selectedClassFilter,
                        'time_scope' => $selectedTimeScope,
                    ])) ?>#class-reservations"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Back to list')) ?></a>
                  </div>
                  <div class="session-stats">
                    <div><strong><?= (int) ($selectedSessionCounts['total'] ?? 0) ?></strong><span><?= htmlspecialchars(sb_admin_text($selectedLang, 'Total')) ?></span></div>
                    <div><strong><?= (int) ($selectedSessionCounts['new'] ?? 0) ?></strong><span><?= htmlspecialchars(sb_admin_text($selectedLang, 'New')) ?></span></div>
                    <div><strong><?= (int) ($selectedSessionCounts['confirmed'] ?? 0) ?></strong><span><?= htmlspecialchars(sb_admin_text($selectedLang, 'Confirmed')) ?></span></div>
                    <div><strong><?= (int) ($selectedSessionCounts['waitlisted'] ?? 0) ?></strong><span><?= htmlspecialchars(sb_admin_text($selectedLang, 'Waitlisted')) ?></span></div>
                    <div><strong><?= (int) ($selectedSessionCounts['attended'] ?? 0) ?></strong><span><?= htmlspecialchars(sb_admin_text($selectedLang, 'Attended')) ?></span></div>
                  </div>
                  <div class="session-guest-list">
                    <?php if ($selectedSessionReservations === []): ?>
                      <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'No guests booked for this session yet.')) ?></p>
                    <?php else: ?>
                      <?php foreach ($selectedSessionReservations as $reservation): ?>
                        <?php
                          $ackEmail = sb_admin_reservation_email_status($reservation, 'ack');
                          $statusEmail = sb_admin_reservation_email_status($reservation, 'status');
                          $hasEmail = trim((string) ($reservation['email'] ?? '')) !== '';
                        ?>
                        <div class="session-guest-item">
                          <div class="session-guest-top">
                            <strong><?= htmlspecialchars((string) ($reservation['customer_name'] ?? '')) ?></strong>
                            <span class="status-badge status-<?= htmlspecialchars((string) ($reservation['status'] ?? 'new')) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, ucfirst((string) ($reservation['status'] ?? 'new')))) ?></span>
                          </div>
                          <div class="session-guest-meta">
                            <div><?= htmlspecialchars(($reservation['email'] ?? '') !== '' ? (string) ($reservation['email'] ?? '') : (string) ($reservation['phone'] ?? '')) ?></div>
                            <?php if (($reservation['attendance'] ?? 'pending') !== 'pending'): ?>
                              <div><?= htmlspecialchars(sb_admin_text($selectedLang, 'Attendance')) ?>: <?= htmlspecialchars(sb_admin_text($selectedLang, ucfirst((string) ($reservation['attendance'] ?? 'pending')))) ?></div>
                            <?php endif; ?>
                            <div><?= htmlspecialchars(sb_admin_text($selectedLang, 'Guest note')) ?>: <?= htmlspecialchars(($reservation['message'] ?? '') !== '' ? (string) ($reservation['message'] ?? '') : sb_admin_text($selectedLang, 'No guest note.')) ?></div>
                          </div>
                          <div class="session-email-grid">
                            <div class="session-email-card">
                              <span><?= htmlspecialchars(sb_admin_text($selectedLang, 'Initial email')) ?></span>
                              <strong><?= htmlspecialchars($hasEmail ? sb_admin_text($selectedLang, sb_admin_reservation_email_status_label((string) ($ackEmail['status'] ?? 'not_sent'))) : sb_admin_text($selectedLang, 'No email address provided.')) ?></strong>
                              <small><?= htmlspecialchars($ackEmail['sent_at'] !== '' ? sb_admin_format_admin_datetime((string) $ackEmail['sent_at']) : sb_admin_text($selectedLang, 'Not sent yet')) ?></small>
                            </div>
                            <div class="session-email-card">
                              <span><?= htmlspecialchars(sb_admin_text($selectedLang, 'Status email')) ?></span>
                              <strong><?= htmlspecialchars($hasEmail ? (($statusEmail['last'] ?? '') !== '' ? sb_admin_text($selectedLang, ucfirst((string) $statusEmail['last'])) : sb_admin_text($selectedLang, 'Not sent yet')) : sb_admin_text($selectedLang, 'No email address provided.')) ?></strong>
                              <small><?= htmlspecialchars($statusEmail['sent_at'] !== '' ? sb_admin_format_admin_datetime((string) $statusEmail['sent_at']) : sb_admin_text($selectedLang, 'Not sent yet')) ?></small>
                            </div>
                          </div>
                          <div class="session-guest-actions">
                            <form method="post">
                              <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
                              <input type="hidden" name="action" value="quick_reservation_update">
                              <input type="hidden" name="reservation_id" value="<?= htmlspecialchars((string) ($reservation['id'] ?? '')) ?>">
                              <input type="hidden" name="quick_status" value="confirmed">
                              <button class="quick-button primary" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Confirm')) ?></button>
                            </form>
                            <form method="post">
                              <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
                              <input type="hidden" name="action" value="quick_reservation_update">
                              <input type="hidden" name="reservation_id" value="<?= htmlspecialchars((string) ($reservation['id'] ?? '')) ?>">
                              <input type="hidden" name="quick_status" value="waitlisted">
                              <button class="quick-button" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Waitlist')) ?></button>
                            </form>
                            <form method="post">
                              <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
                              <input type="hidden" name="action" value="quick_reservation_update">
                              <input type="hidden" name="reservation_id" value="<?= htmlspecialchars((string) ($reservation['id'] ?? '')) ?>">
                              <input type="hidden" name="quick_status" value="cancelled">
                              <button class="quick-button danger" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Cancel')) ?></button>
                            </form>
                            <form method="post">
                              <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
                              <input type="hidden" name="action" value="quick_reservation_update">
                              <input type="hidden" name="reservation_id" value="<?= htmlspecialchars((string) ($reservation['id'] ?? '')) ?>">
                              <input type="hidden" name="quick_attendance" value="attended">
                              <button class="quick-button" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Mark attended')) ?></button>
                            </form>
                            <form method="post">
                              <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
                              <input type="hidden" name="action" value="quick_reservation_update">
                              <input type="hidden" name="reservation_id" value="<?= htmlspecialchars((string) ($reservation['id'] ?? '')) ?>">
                              <input type="hidden" name="quick_attendance" value="no-show">
                              <button class="quick-button" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Mark no-show')) ?></button>
                            </form>
                            <?php if ($hasEmail): ?>
                              <form method="post">
                                <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
                                <input type="hidden" name="action" value="resend_reservation_email">
                                <input type="hidden" name="reservation_id" value="<?= htmlspecialchars((string) ($reservation['id'] ?? '')) ?>">
                                <button class="quick-button" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Resend email')) ?></button>
                              </form>
                            <?php endif; ?>
                          </div>
                          <details class="session-note-shell">
                            <summary><?= htmlspecialchars(sb_admin_text($selectedLang, 'Edit note')) ?></summary>
                            <form class="session-detail-form" method="post">
                              <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
                              <input type="hidden" name="action" value="update_reservation">
                              <input type="hidden" name="reservation_id" value="<?= htmlspecialchars((string) ($reservation['id'] ?? '')) ?>">
                              <input type="hidden" name="reservation_status" value="<?= htmlspecialchars((string) ($reservation['status'] ?? 'new')) ?>">
                              <input type="hidden" name="reservation_attendance" value="<?= htmlspecialchars((string) ($reservation['attendance'] ?? 'pending')) ?>">
                              <label>
                                <span><?= htmlspecialchars(sb_admin_text($selectedLang, 'Organizer note')) ?></span>
                                <textarea name="reservation_admin_note"><?= htmlspecialchars((string) ($reservation['admin_note'] ?? '')) ?></textarea>
                              </label>
                              <div class="actions">
                                <button class="primary" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Save note')) ?></button>
                              </div>
                            </form>
                          </details>
                        </div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                </div>
              </section>
            <?php endif; ?>
            <?php if (!$selectedSessionView): ?>
            <div class="session-overview">
              <?php foreach ($upcomingAdminSessions as $session): ?>
                <?php
                  $sessionReservations = sb_admin_session_reservations($filteredReservations, $session);
                  $sessionCounts = sb_admin_reservation_status_counts($sessionReservations);
                ?>
                <article class="session-card">
                  <div class="session-top">
                    <div>
                      <h3><?= htmlspecialchars((string) ($session['class_title'] ?? '')) ?></h3>
                      <p><?= htmlspecialchars((string) ($session['summary_label'] ?? '')) ?></p>
                      <?php if (($session['location'] ?? '') !== ''): ?>
                        <p><?= htmlspecialchars((string) ($session['location'] ?? '')) ?></p>
                      <?php endif; ?>
                    </div>
                    <strong><?= (int) ($sessionCounts['total'] ?? 0) ?> <?= htmlspecialchars(sb_admin_text($selectedLang, 'Guests')) ?></strong>
                  </div>
                  <div class="actions">
                    <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query([
                        'lang' => $selectedLang,
                        'section' => $selectedAdminSection,
                        'class_filter' => $selectedClassFilter,
                        'time_scope' => $selectedTimeScope,
                        'session_view' => (string) ($session['id'] ?? ''),
                    ])) ?>#session-detail"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Open session')) ?></a>
                  </div>
                  <div class="session-stats">
                    <div><strong><?= (int) ($sessionCounts['total'] ?? 0) ?></strong><span><?= htmlspecialchars(sb_admin_text($selectedLang, 'Total')) ?></span></div>
                    <div><strong><?= (int) ($sessionCounts['new'] ?? 0) ?></strong><span><?= htmlspecialchars(sb_admin_text($selectedLang, 'New')) ?></span></div>
                    <div><strong><?= (int) ($sessionCounts['confirmed'] ?? 0) ?></strong><span><?= htmlspecialchars(sb_admin_text($selectedLang, 'Confirmed')) ?></span></div>
                    <div><strong><?= (int) ($sessionCounts['waitlisted'] ?? 0) ?></strong><span><?= htmlspecialchars(sb_admin_text($selectedLang, 'Waitlisted')) ?></span></div>
                    <div><strong><?= (int) ($sessionCounts['attended'] ?? 0) ?></strong><span><?= htmlspecialchars(sb_admin_text($selectedLang, 'Attended')) ?></span></div>
                  </div>
                  <div class="session-guest-list">
                    <?php if ($sessionReservations === []): ?>
                      <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'No guests booked for this session yet.')) ?></p>
                    <?php else: ?>
                      <?php foreach ($sessionReservations as $reservation): ?>
                        <div class="session-guest-item">
                          <div class="session-guest-top">
                            <strong><?= htmlspecialchars((string) ($reservation['customer_name'] ?? '')) ?></strong>
                            <span class="status-badge status-<?= htmlspecialchars((string) ($reservation['status'] ?? 'new')) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, ucfirst((string) ($reservation['status'] ?? 'new')))) ?></span>
                          </div>
                          <div class="session-guest-meta">
                            <div><?= htmlspecialchars(($reservation['email'] ?? '') !== '' ? (string) ($reservation['email'] ?? '') : (string) ($reservation['phone'] ?? '')) ?></div>
                            <?php if (($reservation['attendance'] ?? 'pending') !== 'pending'): ?>
                              <div><?= htmlspecialchars(sb_admin_text($selectedLang, 'Attendance')) ?>: <?= htmlspecialchars(sb_admin_text($selectedLang, ucfirst((string) ($reservation['attendance'] ?? 'pending')))) ?></div>
                            <?php endif; ?>
                            <div><?= htmlspecialchars(sb_admin_text($selectedLang, 'Guest note')) ?>: <?= htmlspecialchars(($reservation['message'] ?? '') !== '' ? (string) ($reservation['message'] ?? '') : sb_admin_text($selectedLang, 'No guest note.')) ?></div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if (in_array($selectedAdminSection, ['dashboard', 'bookings', 'inquiries', 'product-orders'], true)): ?>
        <section class="panel">
          <?php
            $isProductOrderSection = $selectedAdminSection === 'product-orders';
            $visibleInquiries = $isProductOrderSection ? $productOrders : $inquiries;
            $detailInquiry = $selectedInquiryId !== '' ? sb_admin_find_inquiry($visibleInquiries, $selectedInquiryId) : null;
          ?>
          <h2><?= htmlspecialchars($isProductOrderSection ? sb_admin_text($selectedLang, 'Product Orders') : ($selectedAdminSection === 'inquiries' ? sb_admin_text($selectedLang, 'Inquiries') : $langContent['ui']['inquiries_heading'])) ?></h2>
          <?php if ($selectedAdminSection === 'product-orders'): ?>
            <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Manage product orders separately from general inquiries.')) ?></p>
          <?php endif; ?>
          <?php if ($selectedInquiryId !== ''): ?>
            <?php if ($detailInquiry): ?>
              <div class="inquiry-detail">
                <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Inquiry details')) ?></p>
                <div class="inquiry-detail-grid">
                  <?php foreach (sb_admin_inquiry_detail_rows($detailInquiry) as $label => $value): ?>
                    <?php if ($label !== 'Message' && trim((string) $value) !== ''): ?>
                      <article class="inquiry-detail-card">
                        <strong><?= htmlspecialchars(sb_admin_text($selectedLang, $label)) ?></strong>
                        <p><?= htmlspecialchars((string) $value) ?></p>
                      </article>
                    <?php endif; ?>
                  <?php endforeach; ?>
                  <article class="inquiry-detail-card" style="grid-column: 1 / -1;">
                    <strong><?= htmlspecialchars(sb_admin_text($selectedLang, 'Message')) ?></strong>
                    <p><?= htmlspecialchars(trim((string) ($detailInquiry['message'] ?? '')) !== '' ? (string) ($detailInquiry['message'] ?? '') : sb_admin_text($selectedLang, 'No message provided.')) ?></p>
                  </article>
                </div>
                <div>
                  <a class="inline-link" href="<?= htmlspecialchars(sb_admin_query(['lang' => $selectedLang, 'section' => $selectedAdminSection, 'class_filter' => $selectedClassFilter, 'time_scope' => $selectedTimeScope])) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Back to inquiries')) ?></a>
                </div>
              </div>
            <?php else: ?>
              <p class="panel-note"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Inquiry not found.')) ?></p>
            <?php endif; ?>
          <?php endif; ?>
          <?php if ($visibleInquiries === []): ?>
            <p><?= htmlspecialchars(sb_admin_text($selectedLang, $isProductOrderSection ? 'No product orders yet.' : 'No inquiries yet.')) ?></p>
          <?php else: ?>
            <table>
              <thead>
                <tr>
                  <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'When')) ?></th>
                  <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Type')) ?></th>
                  <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Name')) ?></th>
                  <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Contact')) ?></th>
                  <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Item')) ?></th>
                  <?php if ($isProductOrderSection): ?>
                    <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Quantity')) ?></th>
                    <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Order status')) ?></th>
                  <?php endif; ?>
                  <th><?= htmlspecialchars(sb_admin_text($selectedLang, 'Actions')) ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($visibleInquiries as $inquiry): ?>
                  <?php $viewHref = sb_admin_query(['lang' => $selectedLang, 'section' => $selectedAdminSection, 'class_filter' => $selectedClassFilter, 'time_scope' => $selectedTimeScope, 'inquiry' => (string) ($inquiry['_id'] ?? '')]); ?>
                  <tr>
                    <td><?= htmlspecialchars($inquiry['ts'] ?? '') ?></td>
                    <td><?= htmlspecialchars(sb_admin_text($selectedLang, sb_admin_inquiry_type_label($inquiry))) ?></td>
                    <td><?= htmlspecialchars($inquiry['customer_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars(($inquiry['email'] ?? '') !== '' ? $inquiry['email'] : ($inquiry['phone'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($inquiry['item_title'] ?? '') ?></td>
                    <?php if ($isProductOrderSection): ?>
                      <td><?= htmlspecialchars((string) ($inquiry['quantity'] ?? '1')) ?></td>
                      <td>
                        <form method="post" class="actions">
                          <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
                          <input type="hidden" name="action" value="update_product_order_status">
                          <input type="hidden" name="inquiry_id" value="<?= htmlspecialchars((string) ($inquiry['_id'] ?? '')) ?>">
                          <select name="order_status">
                            <?= sb_admin_select_option('new', (string) ($inquiry['order_status'] ?? 'new'), sb_admin_text($selectedLang, 'New order')) ?>
                            <?= sb_admin_select_option('confirmed', (string) ($inquiry['order_status'] ?? 'new'), sb_admin_text($selectedLang, 'Confirmed order')) ?>
                            <?= sb_admin_select_option('cancelled', (string) ($inquiry['order_status'] ?? 'new'), sb_admin_text($selectedLang, 'Cancelled')) ?>
                            <?= sb_admin_select_option('shipped', (string) ($inquiry['order_status'] ?? 'new'), sb_admin_text($selectedLang, 'Shipped order')) ?>
                          </select>
                          <button class="secondary" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Save')) ?></button>
                        </form>
                      </td>
                    <?php endif; ?>
                    <td>
                      <div class="actions">
                        <a class="inline-link" href="<?= htmlspecialchars($viewHref) ?>"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Open inquiry')) ?></a>
                        <form method="post" onsubmit="return confirm('Delete this inquiry?');">
                          <?= sb_admin_hidden($token, $selectedLang, $selectedAdminSection, $selectedClassFilter, $selectedItemId, $selectedMode) ?>
                          <input type="hidden" name="action" value="delete_inquiry">
                          <input type="hidden" name="inquiry_id" value="<?= htmlspecialchars((string) ($inquiry['_id'] ?? '')) ?>">
                          <button class="danger" type="submit"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Delete inquiry')) ?></button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if (in_array($selectedAdminSection, ['dashboard', 'brand', 'media', 'seo', 'ui', 'products', 'events', 'clients', 'visit-packs', 'testimonials', 'classes'], true)): ?>
        <section class="panel">
          <h2><?= htmlspecialchars(sb_admin_text($selectedLang, 'Access')) ?></h2>
          <p><?= htmlspecialchars(sb_admin_text($selectedLang, 'Admin access now uses a secure session. Existing token links can still bootstrap a session while the new password login is being rolled out.')) ?></p>
          <p><a href="<?= htmlspecialchars(shine_bright_admin_login_url($selectedLang)) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Admin login')) ?></a></p>
          <p><?= htmlspecialchars(sb_admin_text($selectedLang, 'Current public site:')) ?></p>
          <p><a href="./index.php?lang=<?= htmlspecialchars($selectedLang) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars(sb_admin_text($selectedLang, 'Preview current language')) ?></a></p>
        </section>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <script>
    document.querySelectorAll('[data-schedule-editor]').forEach(function (editor) {
      var list = editor.querySelector('[data-schedule-list]');
      var addButton = editor.querySelector('[data-add-schedule]');
      if (!list || !addButton) {
        return;
      }

      function bindRow(row) {
        var removeButton = row.querySelector('[data-remove-schedule]');
        if (!removeButton) {
          return;
        }
        removeButton.addEventListener('click', function () {
          var rows = list.querySelectorAll('[data-schedule-row]');
          if (rows.length <= 1) {
            row.querySelectorAll('input, select').forEach(function (field) {
              if (field.tagName === 'SELECT') {
                field.selectedIndex = 0;
              } else {
                field.value = '';
              }
            });
            return;
          }
          row.remove();
        });
      }

      list.querySelectorAll('[data-schedule-row]').forEach(bindRow);

      addButton.addEventListener('click', function () {
        var firstRow = list.querySelector('[data-schedule-row]');
        if (!firstRow) {
          return;
        }
        var clone = firstRow.cloneNode(true);
        clone.querySelectorAll('input').forEach(function (field) {
          field.value = '';
        });
        clone.querySelectorAll('select').forEach(function (field) {
          field.selectedIndex = 0;
        });
        bindRow(clone);
        list.appendChild(clone);
      });
    });
    (function () {
      var preview = document.getElementById('product-image-preview');
      if (!preview) return;
      var bgImage = preview.style.backgroundImage;
      if (!bgImage || bgImage === 'none') return;
      var focusX = document.getElementById('field_image_focus_x');
      var focusY = document.getElementById('field_image_focus_y');
      var zoom = document.getElementById('field_image_zoom');
      function updatePreview() {
        if (!bgImage || bgImage === 'none') return;
        var fx = focusX ? focusX.value : '50';
        var fy = focusY ? focusY.value : '50';
        var z = zoom ? (parseInt(zoom.value, 10) || 100) : 100;
        preview.style.backgroundPosition = fx + '% ' + fy + '%';
        preview.style.backgroundSize = z + '% auto';
      }
      document.querySelectorAll('.image-focus-slider').forEach(function (slider) {
        var targetId = slider.getAttribute('data-target');
        var numInput = document.getElementById(targetId);
        if (!numInput) return;
        slider.addEventListener('input', function () {
          numInput.value = this.value;
          updatePreview();
        });
        numInput.addEventListener('input', function () {
          slider.value = this.value;
          updatePreview();
        });
      });
      var zoomSlider = document.querySelector('.image-zoom-slider');
      if (zoomSlider && zoom) {
        zoomSlider.addEventListener('input', function () {
          zoom.value = this.value;
          updatePreview();
        });
        zoom.addEventListener('input', function () {
          zoomSlider.value = this.value;
          updatePreview();
        });
      }
    })();
    (function () {
      var nameFilter = document.getElementById('class-name-filter');
      var attendanceFilter = document.getElementById('class-attendance-filter');
      var countEl = document.getElementById('class-filter-count');
      var cards = document.querySelectorAll('#class-cards-list .session-card');
      if (!nameFilter || !attendanceFilter || cards.length === 0) return;
      function applyFilters() {
        var nameQuery = (nameFilter.value || '').toLowerCase().trim();
        var minAttendance = parseInt(attendanceFilter.value || '0', 10) || 0;
        var visible = 0;
        cards.forEach(function (card) {
          var className = card.getAttribute('data-class-name') || '';
          var totalBookings = parseInt(card.getAttribute('data-total-bookings') || '0', 10);
          var nameMatch = nameQuery === '' || className.indexOf(nameQuery) !== -1;
          var attendanceMatch = totalBookings >= minAttendance;
          if (nameMatch && attendanceMatch) {
            card.style.display = '';
            visible++;
          } else {
            card.style.display = 'none';
          }
        });
        if (countEl) {
          countEl.textContent = visible + ' / ' + cards.length + ' ' + (visible === 1 ? 'class' : 'classes');
        }
      }
      nameFilter.addEventListener('input', applyFilters);
      attendanceFilter.addEventListener('change', applyFilters);
      applyFilters();
    })();
  </script>
</body>
</html>
