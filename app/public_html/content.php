<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/runtime-data.php';

define('SHINE_BRIGHT_CONTENT_SCHEMA_VERSION', 2);

function shine_bright_resolve_lang(): string
{
    if (isset($_GET['lang']) && is_string($_GET['lang'])) {
        $lang = strtolower(trim($_GET['lang']));
        if ($lang === 'bg' || $lang === 'en') {
            return $lang;
        }
    }

    return 'bg';
}

function shine_bright_default_content(): array
{
    return [
        'bg' => [
            'meta' => [
                'title' => 'Shine Bright Yoga | Йога, събития и подбрани продукти',
                'description' => 'Shine Bright Yoga е бутиков йога и wellness бранд на Мария Михайлова с класове, специални събития и внимателно подбрани продукти.',
            ],
            'ui' => [
                'nav_classes' => 'Класове',
                'nav_events' => 'Събития',
                'nav_shop' => 'Магазин',
                'nav_founder' => 'Мария',
                'nav_contact' => 'Контакт',
                'header_cta' => 'Запази място',
                'brandmark_home' => 'Начало',
                'hero_eyebrow' => 'Йога и Wellness бранд',
                'hero_primary_cta' => 'Виж класовете',
                'hero_secondary_cta' => 'Разгледай продуктите',
                'stats_classes' => 'редовни практики',
                'stats_events' => 'специални събития',
                'stats_products' => 'подбрани продукта',
                'classes_eyebrow' => 'Практика',
                'classes_heading' => 'Редовни практики за хора, които търсят ясен седмичен ритъм, спокойно водене и устойчивост.',
                'duration' => 'Продължителност',
                'location' => 'Локация',
                'open_maps' => 'Отвори в Google Maps',
                'level' => 'Ниво',
                'reserve_spot' => 'Запази място',
                'select_schedule' => 'Избери график',
                'schedule' => 'График',
                'schedule_placeholder' => 'Избери ден и час',
                'view_details' => 'Виж детайли',
                'back_home' => 'Към началото',
                'back_to_classes' => 'Обратно към класовете',
                'back_to_events' => 'Обратно към събитията',
                'back_to_products' => 'Обратно към продуктите',
                'date' => 'Дата',
                'time' => 'Час',
                'price' => 'Цена',
                'category' => 'Категория',
                'detail_label' => 'Подходящо за',
                'share_page' => 'Сподели тази страница',
                'reserve_dialog_title' => 'Запази място за ',
                'reserve_submit' => 'Запази място',
                'reserve_context' => 'Заявявате място за клас.',
                'events_eyebrow' => 'Събития',
                'events_heading' => 'Специални събития и малки уъркшоп формати за по-дълбока среща с практиката.',
                'events_intro' => 'Тук форматът е по-дълъг, групите са по-малки, а преживяването е по-тематично и по-цялостно от редовния клас.',
                'join_event' => 'Запиши се',
                'event_dialog_title' => 'Заяви участие за ',
                'event_submit' => 'Заяви участие',
                'event_context' => 'Заявявате участие в събитие.',
                'shop_eyebrow' => 'Подбрани продукти',
                'shop_heading' => 'Продукти, представени не като списък, а като естествено продължение на усещането от практиката.',
                'order_product' => 'Поръчай',
                'product_dialog_title' => 'Поръчай ',
                'product_submit' => 'Изпрати поръчка',
                'product_context' => 'Изпращате запитване за продукт.',
                'founder_eyebrow' => 'Мария',
                'approach_eyebrow' => 'Подход',
                'approach_heading' => 'Практика, атмосфера и грижа, поднесени с последователност.',
                'contact_eyebrow' => 'Запитване и записване',
                'contact_heading' => 'Запази място, заяви участие или направи поръчка.',
                'contact_body' => 'За класове, събития и продукти можеш да се свържеш директно по телефон, имейл или чрез кратко запитване.',
                'contact_inquiry_cta' => 'Изпрати запитване',
                'phone_cta' => 'Обади се на Мария',
                'inquiry_eyebrow' => 'Запитване',
                'inquiry_default_title' => 'Запитване или записване',
                'inquiry_product_prefix' => 'Поръчка за ',
                'inquiry_general_prefix' => 'Запитване за ',
                'general_dialog_title' => 'Изпрати запитване за ',
                'general_context' => 'Изпращате общо запитване.',
                'type_label_class' => 'Клас',
                'type_label_event' => 'Събитие',
                'type_label_product' => 'Продукт',
                'type_label_general' => 'Запитване',
                'field_name' => 'Име',
                'field_email' => 'Имейл',
                'field_phone' => 'Телефон',
                'field_quantity' => 'Количество за поръчка',
                'field_message' => 'Съобщение',
                'field_message_placeholder' => 'Споделете удобен ден, въпрос или предпочитание…',
                'field_message_placeholder_class' => 'Споделете предпочитан клас, ден или въпрос…',
                'field_message_placeholder_event' => 'Споделете въпрос, интерес или предпочитание за събитието…',
                'field_message_placeholder_product' => 'Споделете количество, предпочитание или въпрос за продукта…',
                'send_inquiry' => 'Изпрати запитване',
                'cancel' => 'Отказ',
                'sending' => 'Изпращане…',
                'success' => 'Запитването е получено. Ще се свържем с вас скоро.',
                'success_class' => 'Мястото е заявено. Мария ще се свърже с вас за потвърждение.',
                'success_event' => 'Участието е заявено. Мария ще се свърже с вас за потвърждение.',
                'success_product' => 'Поръчката е изпратена. Мария ще се свърже с вас за детайлите.',
                'error_default' => 'Не успяхме да запазим запитването.',
                'close_dialog' => 'Затвори',
                'error_invalid_email' => 'Въведете валиден имейл адрес.',
                'error_invalid_phone' => 'Въведете валиден телефонен номер.',
                'lang_bg' => 'BG',
                'lang_en' => 'EN',
                'language_label' => 'Език',
                'approach_one_title' => 'Ясно водена практика',
                'approach_one_body' => 'Класове с внимание към дишането, структурата и реалното усещане в тялото, а не към показността.',
                'approach_two_title' => 'Спокойна среда',
                'approach_two_body' => 'Място, в което атмосферата, ритъмът и детайлът подкрепят практиката, вместо да я разсейват.',
                'approach_three_title' => 'Малки практики за ежедневието',
                'approach_three_body' => 'Подбрани продукти и събития, които пренасят усещането за баланс отвъд самия клас.',
                'cta_note' => 'Подходящо за хора, които търсят внимателна практика, красива атмосфера и устойчив личен ритъм.',
                'inquiries_heading' => 'Последни запитвания',
            ],
            'brand' => [
                'name' => 'Shine Bright Yoga',
                'headline' => 'Спокойно и красиво място за йога, събития и малки ежедневни практики.',
                'intro' => 'Shine Bright Yoga е личният wellness бранд на Мария Михайлова, създаден около внимателна йога практика, усещане за общност и подбрани продукти, които носят повече баланс и атмосфера в ежедневието.',
                'subintro' => 'Сайтът е замислен като професионален дом на бранда: място, където практиката, събитията и продуктите изглеждат свързани, уверени и лесни за действие.',
                'founder_name' => 'Мария Михайлова',
                'founder_title' => 'Йога инструктор и създател на Shine Bright Yoga',
                'founder_story' => 'Работата на Мария съчетава ясно водене, спокойна атмосфера и внимание към детайла. Идеята зад Shine Bright Yoga е практиката да не свършва в студиото, а да продължава и у дома чрез ритъм, присъствие и малки практики, които човек може реално да поддържа.',
                'hero_text_mode' => 'auto',
                'founder_image_url' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=1200&q=80',
                'hero_video_url' => '',
                'hero_video_poster_url' => '',
                'contact_email' => 'maria@shinebrightyoga.com',
                'contact_phone' => '+359 898 38 29 26',
                'instagram' => '',
            ],
            'classes' => [
                [
                    'id' => 'morning-flow',
                    'title' => 'Сутрешен Flow',
                    'level' => 'Подходящ за всички нива',
                    'price_eur' => '16',
                    'description' => 'Редовна сутрешна практика за ясен старт на деня, плавно събуждане на тялото и по-събран фокус.',
                    'schedules' => [
                        ['id' => 'tuesday-central-sofia', 'weekday' => 'tuesday', 'start_time' => '07:30', 'end_time' => '08:30', 'location' => 'Студио в центъра на София', 'maps_url' => ''],
                        ['id' => 'thursday-central-sofia', 'weekday' => 'thursday', 'start_time' => '07:30', 'end_time' => '08:30', 'location' => 'Студио в центъра на София', 'maps_url' => ''],
                    ],
                ],
                [
                    'id' => 'evening-reset',
                    'title' => 'Вечерен Reset',
                    'level' => 'Леко до средно ниво',
                    'price_eur' => '18',
                    'description' => 'Редовен вечерен клас за освобождаване на напрежението, успокояване на нервната система и по-меко прибиране след деня.',
                    'schedules' => [
                        ['id' => 'wednesday-boutique-studio', 'weekday' => 'wednesday', 'start_time' => '19:00', 'end_time' => '20:15', 'location' => 'Бутиково студио', 'maps_url' => ''],
                        ['id' => 'friday-boutique-studio', 'weekday' => 'friday', 'start_time' => '18:30', 'end_time' => '19:45', 'location' => 'Бутиково студио', 'maps_url' => ''],
                    ],
                ],
                [
                    'id' => 'saturday-ground',
                    'title' => 'Съботно заземяване',
                    'level' => 'Отворен клас',
                    'price_eur' => '19',
                    'description' => 'Уикенд практика с по-дълго начало, стабилни стоящи пози и спокоен финал, която носи устойчив и мек старт на съботата.',
                    'schedules' => [
                        ['id' => 'saturday-city-garden', 'weekday' => 'saturday', 'start_time' => '10:30', 'end_time' => '11:45', 'location' => 'Градска градина / сезонно', 'maps_url' => ''],
                    ],
                ],
            ],
            'events' => [
                [
                    'id' => 'spring-breath',
                    'title' => 'Пролетна breathwork сесия',
                    'start_at' => '2026-04-18T18:30',
                    'end_at' => '2026-04-18T20:30',
                    'location' => 'София, малка група',
                    'maps_url' => '',
                    'price_eur' => '31',
                    'description' => 'Специален двучасов формат с breathwork, мека практика, водено заземяване и чай в спокойна малка група.',
                ],
                [
                    'id' => 'summer-ritual',
                    'title' => 'Лятна работилница за грижа',
                    'start_at' => '2026-05-30T11:00',
                    'end_at' => '2026-05-30T14:00',
                    'location' => 'Shine Bright Yoga Loft',
                    'maps_url' => '',
                    'price_eur' => '46',
                    'description' => 'Малък уъркшоп формат, който съчетава движение, аромат, грижа за себе си и внимателно подбрани детайли около практиката.',
                ],
            ],
            'products' => [
                [
                    'id' => 'ritual-candle',
                    'title' => 'Ритуална свещ Amber',
                    'category' => 'Домашна грижа',
                    'price_eur' => '22',
                    'detail' => 'Вечерна грижа',
                    'short_description' => 'Топъл аромат за по-тиха вечер и по-мека домашна атмосфера.',
                    'image_url' => 'https://images.unsplash.com/photo-1603006905003-be475563bc59?auto=format&fit=crop&w=1200&q=80',
                    'image_focus_x' => '50',
                    'image_focus_y' => '50',
                    'image_zoom' => '100',
                    'description' => 'Топъл аромат с дървесни и смолисти нотки за по-тиха вечер и по-мека домашна атмосфера.',
                ],
                [
                    'id' => 'body-oil',
                    'title' => 'Натурално масло Balance',
                    'category' => 'Грижа за тялото',
                    'price_eur' => '24',
                    'detail' => 'След практика',
                    'short_description' => 'Леко масло след практика с фин билков профил и усещане за комфорт.',
                    'image_url' => 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?auto=format&fit=crop&w=1200&q=80',
                    'image_focus_x' => '50',
                    'image_focus_y' => '50',
                    'image_zoom' => '100',
                    'description' => 'Леко масло за след практика с фин билков профил и усещане за комфорт без тежест.',
                ],
                [
                    'id' => 'room-mist',
                    'title' => 'Grounding room mist',
                    'category' => 'Атмосфера',
                    'price_eur' => '16',
                    'detail' => 'За пространство',
                    'short_description' => 'Спрей за практика у дома, спокойна вечер или бързо освежаване на студиото.',
                    'image_url' => 'https://images.unsplash.com/photo-1611078489935-0cb964de46d6?auto=format&fit=crop&w=1200&q=80',
                    'image_focus_x' => '50',
                    'image_focus_y' => '50',
                    'image_zoom' => '100',
                    'description' => 'Спрей за пространство, подходящ за кът за практика, спокойна вечер у дома или освежаване на студиото.',
                ],
                [
                    'id' => 'ritual-set',
                    'title' => 'Комплект After Class',
                    'category' => 'Комплект',
                    'price_eur' => '48',
                    'detail' => 'Подарък или старт',
                    'short_description' => 'Начален сет със свещ, масло и mist за хора, които искат да пренесат ритуала у дома.',
                    'image_url' => 'https://images.unsplash.com/photo-1515377905703-c4788e51af15?auto=format&fit=crop&w=1200&q=80',
                    'image_focus_x' => '50',
                    'image_focus_y' => '50',
                    'image_zoom' => '100',
                    'description' => 'Начален сет със свещ, масло и mist за хора, които искат да пренесат усещането от класа и у дома.',
                ],
            ],
            'testimonials' => [
                ['id' => 'student-one', 'quote' => 'Практиката с Мария е едновременно прецизна и много човешка. Излизаш по-спокоен, но и по-свързан със себе си.', 'name' => 'Участник в клас'],
                ['id' => 'student-two', 'quote' => 'Тук не става дума само за движение. Има атмосфера, внимание и усещане, че всичко е обмислено.', 'name' => 'Гост на събитие'],
            ],
        ],
        'en' => [
            'meta' => [
                'title' => 'Shine Bright Yoga | Yoga, Events, and Curated Products',
                'description' => 'Shine Bright Yoga is Maria Mihailova’s boutique yoga and wellness brand with classes, special events, and carefully selected products.',
            ],
            'ui' => [
                'nav_classes' => 'Classes',
                'nav_events' => 'Events',
                'nav_shop' => 'Shop',
                'nav_founder' => 'Maria',
                'nav_contact' => 'Contact',
                'header_cta' => 'Book a Class',
                'brandmark_home' => 'Home',
                'hero_eyebrow' => 'Yoga and Wellness Brand',
                'hero_primary_cta' => 'View Classes',
                'hero_secondary_cta' => 'Explore the Shop',
                'stats_classes' => 'regular practices',
                'stats_events' => 'special events',
                'stats_products' => 'curated products',
                'classes_eyebrow' => 'Practice',
                'classes_heading' => 'Regular practices for people looking for a clear weekly rhythm, calm guidance, and consistency.',
                'duration' => 'Duration',
                'location' => 'Location',
                'open_maps' => 'Open in Google Maps',
                'level' => 'Level',
                'reserve_spot' => 'Reserve Spot',
                'select_schedule' => 'Choose schedule',
                'schedule' => 'Schedule',
                'schedule_placeholder' => 'Choose day and time',
                'view_details' => 'View Details',
                'back_home' => 'Back Home',
                'back_to_classes' => 'Back to Classes',
                'back_to_events' => 'Back to Events',
                'back_to_products' => 'Back to Products',
                'date' => 'Date',
                'time' => 'Time',
                'price' => 'Price',
                'category' => 'Category',
                'detail_label' => 'Best for',
                'share_page' => 'Share this page',
                'reserve_dialog_title' => 'Reserve a spot for ',
                'reserve_submit' => 'Reserve Spot',
                'reserve_context' => 'You are requesting a spot for a class.',
                'events_eyebrow' => 'Events',
                'events_heading' => 'Special events and small workshop formats for a deeper encounter with the practice.',
                'events_intro' => 'These formats are longer, more thematic, and held in smaller groups, offering a fuller experience than the regular class.',
                'join_event' => 'Join Event',
                'event_dialog_title' => 'Join ',
                'event_submit' => 'Join Event',
                'event_context' => 'You are requesting a place in this event.',
                'shop_eyebrow' => 'Curated Products',
                'shop_heading' => 'Products presented not as a list, but as a natural extension of the feeling created in practice.',
                'order_product' => 'Order Product',
                'product_dialog_title' => 'Order ',
                'product_submit' => 'Place Order',
                'product_context' => 'You are sending a product inquiry.',
                'founder_eyebrow' => 'Maria',
                'approach_eyebrow' => 'Approach',
                'approach_heading' => 'Practice, atmosphere, and care delivered with consistency.',
                'contact_eyebrow' => 'Bookings and Inquiries',
                'contact_heading' => 'Reserve a class, join an event, or place a product order.',
                'contact_body' => 'For classes, events, and products, you can reach out directly by phone, email, Instagram, or a short inquiry.',
                'contact_inquiry_cta' => 'Send Inquiry',
                'phone_cta' => 'Call Maria',
                'inquiry_eyebrow' => 'Inquiry',
                'inquiry_default_title' => 'Reserve or Order',
                'inquiry_product_prefix' => 'Order ',
                'inquiry_general_prefix' => 'Inquire about ',
                'general_dialog_title' => 'Send an inquiry about ',
                'general_context' => 'You are sending a general inquiry.',
                'type_label_class' => 'Class',
                'type_label_event' => 'Event',
                'type_label_product' => 'Product',
                'type_label_general' => 'Inquiry',
                'field_name' => 'Name',
                'field_email' => 'Email',
                'field_phone' => 'Phone',
                'field_quantity' => 'Order quantity',
                'field_message' => 'Message',
                'field_message_placeholder' => 'Share a preferred date, question, or request…',
                'field_message_placeholder_class' => 'Share a preferred class, date, or question…',
                'field_message_placeholder_event' => 'Share a question, interest, or request about the event…',
                'field_message_placeholder_product' => 'Share quantity, preference, or a question about the product…',
                'send_inquiry' => 'Send Inquiry',
                'cancel' => 'Cancel',
                'sending' => 'Sending…',
                'success' => 'Inquiry received. We will get back to you shortly.',
                'success_class' => 'Your reservation request was sent. Maria will contact you to confirm.',
                'success_event' => 'Your event request was sent. Maria will contact you to confirm.',
                'success_product' => 'Your product request was sent. Maria will contact you with the details.',
                'error_default' => 'Unable to save inquiry.',
                'close_dialog' => 'Close',
                'error_invalid_email' => 'Enter a valid email address.',
                'error_invalid_phone' => 'Enter a valid phone number.',
                'lang_bg' => 'BG',
                'lang_en' => 'EN',
                'language_label' => 'Language',
                'approach_one_title' => 'Clear, guided practice',
                'approach_one_body' => 'Classes shaped around breath, structure, and how the body actually feels, not around performance.',
                'approach_two_title' => 'A calm environment',
                'approach_two_body' => 'A setting where atmosphere, pace, and detail support the practice rather than distract from it.',
                'approach_three_title' => 'Small practices for daily life',
                'approach_three_body' => 'Carefully chosen products and events that carry the feeling of practice beyond the class itself.',
                'cta_note' => 'Suitable for people looking for thoughtful practice, a refined atmosphere, and a steadier personal rhythm.',
                'inquiries_heading' => 'Recent inquiries',
            ],
            'brand' => [
                'name' => 'Shine Bright Yoga',
                'headline' => 'A calm and beautifully considered home for yoga, events, and everyday practices.',
                'intro' => 'Shine Bright Yoga is the personal wellness brand of Maria Mihailova, built around thoughtful yoga practice, a strong sense of atmosphere, and carefully selected products that bring more balance into daily life.',
                'subintro' => 'The site is designed as a professional home for the brand: a place where practice, events, and products feel connected, confident, and easy to act on.',
                'founder_name' => 'Maria Mihailova',
                'founder_title' => 'Yoga Instructor and Founder of Shine Bright Yoga',
                'founder_story' => 'Maria’s work combines clear guidance, a calm environment, and a refined eye for detail. The idea behind Shine Bright Yoga is that the practice should not end in the studio. It should continue at home through rhythm, presence, and small practices that people can genuinely keep in their lives.',
                'hero_text_mode' => 'auto',
                'founder_image_url' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=1200&q=80',
                'hero_video_url' => '',
                'hero_video_poster_url' => '',
                'contact_email' => 'maria@shinebrightyoga.com',
                'contact_phone' => '+359 898 38 29 26',
                'instagram' => '',
            ],
            'classes' => [
                ['id' => 'morning-flow', 'title' => 'Morning Flow', 'level' => 'Open level', 'price_eur' => '16', 'description' => 'A regular morning practice for a clear start to the day, fluid movement, and a more collected focus.', 'schedules' => [['id' => 'tuesday-central-sofia', 'weekday' => 'tuesday', 'start_time' => '07:30', 'end_time' => '08:30', 'location' => 'Central Sofia studio', 'maps_url' => ''], ['id' => 'thursday-central-sofia', 'weekday' => 'thursday', 'start_time' => '07:30', 'end_time' => '08:30', 'location' => 'Central Sofia studio', 'maps_url' => '']]],
                ['id' => 'evening-reset', 'title' => 'Evening Reset', 'level' => 'Gentle to intermediate', 'price_eur' => '18', 'description' => 'A regular evening class for releasing tension, settling the nervous system, and landing more softly after the day.', 'schedules' => [['id' => 'wednesday-boutique-studio', 'weekday' => 'wednesday', 'start_time' => '19:00', 'end_time' => '20:15', 'location' => 'Boutique studio', 'maps_url' => ''], ['id' => 'friday-boutique-studio', 'weekday' => 'friday', 'start_time' => '18:30', 'end_time' => '19:45', 'location' => 'Boutique studio', 'maps_url' => '']]],
                ['id' => 'saturday-ground', 'title' => 'Saturday Grounding', 'level' => 'Open class', 'price_eur' => '19', 'description' => 'A weekend practice with a longer opening, steady standing work, and a calm finish that sets the tone for Saturday.', 'schedules' => [['id' => 'saturday-city-garden', 'weekday' => 'saturday', 'start_time' => '10:30', 'end_time' => '11:45', 'location' => 'City garden / seasonal', 'maps_url' => '']]],
            ],
            'events' => [
                ['id' => 'spring-breath', 'title' => 'Spring Breathwork Session', 'start_at' => '2026-04-18T18:30', 'end_at' => '2026-04-18T20:30', 'location' => 'Sofia, small group setting', 'maps_url' => '', 'price_eur' => '31', 'description' => 'A special two-hour format with breathwork, gentle movement, guided grounding, and tea in a quiet small-group setting.'],
                ['id' => 'summer-ritual', 'title' => 'Summer Care Workshop', 'start_at' => '2026-05-30T11:00', 'end_at' => '2026-05-30T14:00', 'location' => 'Shine Bright Yoga Loft', 'maps_url' => '', 'price_eur' => '46', 'description' => 'A small workshop format combining movement, scent, self-care, and thoughtfully selected details around the practice.'],
            ],
            'products' => [
                ['id' => 'ritual-candle', 'title' => 'Amber Candle', 'category' => 'Home care', 'price_eur' => '22', 'detail' => 'Evening unwind', 'short_description' => 'A warm scent for quieter evenings and a softer home atmosphere.', 'image_url' => 'https://images.unsplash.com/photo-1603006905003-be475563bc59?auto=format&fit=crop&w=1200&q=80', 'image_focus_x' => '50', 'image_focus_y' => '50', 'image_zoom' => '100', 'description' => 'A warm scent profile with woody and resinous notes for quieter evenings and a softer home atmosphere.'],
                ['id' => 'body-oil', 'title' => 'Balance Natural Body Oil', 'category' => 'Body care', 'price_eur' => '24', 'detail' => 'Post-practice', 'short_description' => 'A lightweight post-practice oil with a subtle herbal finish.', 'image_url' => 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?auto=format&fit=crop&w=1200&q=80', 'image_focus_x' => '50', 'image_focus_y' => '50', 'image_zoom' => '100', 'description' => 'A lightweight post-practice oil with a subtle herbal finish and a comfortable non-heavy feel.'],
                ['id' => 'room-mist', 'title' => 'Grounding Room Mist', 'category' => 'Atmosphere', 'price_eur' => '16', 'detail' => 'For your space', 'short_description' => 'A room spray for a home practice corner, calm evening, or studio reset.', 'image_url' => 'https://images.unsplash.com/photo-1611078489935-0cb964de46d6?auto=format&fit=crop&w=1200&q=80', 'image_focus_x' => '50', 'image_focus_y' => '50', 'image_zoom' => '100', 'description' => 'A room spray for a home practice corner, a calm evening, or a studio reset between sessions.'],
                ['id' => 'ritual-set', 'title' => 'After Class Care Set', 'category' => 'Bundle', 'price_eur' => '48', 'detail' => 'Gift or starter', 'short_description' => 'A starter set with candle, oil, and mist to carry the class atmosphere home.', 'image_url' => 'https://images.unsplash.com/photo-1515377905703-c4788e51af15?auto=format&fit=crop&w=1200&q=80', 'image_focus_x' => '50', 'image_focus_y' => '50', 'image_zoom' => '100', 'description' => 'A starter set with candle, oil, and mist for students who want to carry the class atmosphere home.'],
            ],
            'testimonials' => [
                ['id' => 'student-one', 'quote' => 'Maria’s classes feel both precise and deeply human. You leave calmer, but also more connected to yourself.', 'name' => 'Class participant'],
                ['id' => 'student-two', 'quote' => 'It is not only about movement. There is atmosphere, care, and a sense that everything has been thoughtfully considered.', 'name' => 'Event guest'],
            ],
        ],
    ];
}

function shine_bright_array_merge_recursive(array $default, array $current): array
{
    foreach ($current as $key => $value) {
        if (isset($default[$key]) && is_array($default[$key]) && is_array($value)) {
            $default[$key] = shine_bright_array_merge_recursive($default[$key], $value);
        } else {
            $default[$key] = $value;
        }
    }

    return $default;
}

function shine_bright_default_content_document(): array
{
    return [
        '_schema_version' => SHINE_BRIGHT_CONTENT_SCHEMA_VERSION,
        'bg' => shine_bright_default_content()['bg'],
        'en' => shine_bright_default_content()['en'],
    ];
}

function shine_bright_content_document_version(array $document): int
{
    $version = $document['_schema_version'] ?? 0;
    return is_int($version) ? $version : (is_numeric($version) ? (int) $version : 0);
}

function shine_bright_migrate_content_document(array $document): array
{
    $version = shine_bright_content_document_version($document);

    if ($version < 1) {
        $document['_schema_version'] = 1;
        $version = 1;
    }

    if ($version < 2) {
        foreach (['bg', 'en'] as $lang) {
            if (!isset($document[$lang]['classes']) || !is_array($document[$lang]['classes'])) {
                continue;
            }

            foreach ($document[$lang]['classes'] as $index => $class) {
                if (!is_array($class)) {
                    continue;
                }
                if (isset($class['schedules']) && is_array($class['schedules']) && $class['schedules'] !== []) {
                    continue;
                }

                $weekday = shine_bright_weekday_from_datetime(isset($class['start_at']) ? (string) $class['start_at'] : '');
                $startTime = '';
                $endTime = '';
                $startDate = shine_bright_datetime(isset($class['start_at']) ? (string) $class['start_at'] : '');
                $endDate = shine_bright_datetime(isset($class['end_at']) ? (string) $class['end_at'] : '');
                if ($startDate) {
                    $startTime = $startDate->format('H:i');
                }
                if ($endDate) {
                    $endTime = $endDate->format('H:i');
                }

                $document[$lang]['classes'][$index]['schedules'] = [[
                    'id' => 'default',
                    'weekday' => $weekday,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'location' => trim((string) ($class['location'] ?? '')),
                    'maps_url' => trim((string) ($class['maps_url'] ?? '')),
                ]];
            }
        }

        $document['_schema_version'] = 2;
        $version = 2;
    }

    $document['_schema_version'] = SHINE_BRIGHT_CONTENT_SCHEMA_VERSION;

    return $document;
}

function shine_bright_load_content(): array
{
    $default = shine_bright_default_content_document();
    shine_bright_ensure_data_dir();

    if (!is_file(SHINE_BRIGHT_CONTENT_PATH)) {
        file_put_contents(SHINE_BRIGHT_CONTENT_PATH, json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $default;
    }

    $json = file_get_contents(SHINE_BRIGHT_CONTENT_PATH);
    $decoded = is_string($json) ? json_decode($json, true) : null;

    if (!is_array($decoded)) {
        return $default;
    }

    $migrated = shine_bright_migrate_content_document($decoded);
    $merged = shine_bright_array_merge_recursive($default, $migrated);
    $merged['_schema_version'] = SHINE_BRIGHT_CONTENT_SCHEMA_VERSION;

    if ($merged !== $decoded) {
        file_put_contents(SHINE_BRIGHT_CONTENT_PATH, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    return $merged;
}

function shine_bright_save_content(array $content): void
{
    shine_bright_ensure_data_dir();
    $content['_schema_version'] = SHINE_BRIGHT_CONTENT_SCHEMA_VERSION;
    file_put_contents(SHINE_BRIGHT_CONTENT_PATH, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function shine_bright_find_content_item(array $content, string $lang, string $section, string $id): ?array
{
    if (!isset($content[$lang][$section]) || !is_array($content[$lang][$section])) {
        return null;
    }

    foreach ($content[$lang][$section] as $item) {
        if (!is_array($item)) {
            continue;
        }
        if ((string) ($item['id'] ?? '') === $id) {
            return $item;
        }
    }

    return null;
}

function shine_bright_find_public_content_item(array $content, string $lang, string $section, string $id): ?array
{
    $item = shine_bright_find_content_item($content, $lang, $section, $id);
    if ($item) {
        return shine_bright_normalize_content_item($section, $item);
    }

    $fallbackItem = shine_bright_find_content_item($content, shine_bright_content_fallback_lang($lang), $section, $id);
    return $fallbackItem ? shine_bright_normalize_content_item($section, $fallbackItem) : null;
}

function shine_bright_content_sections(): array
{
    return ['classes', 'events', 'products', 'testimonials'];
}

function shine_bright_content_item_templates(): array
{
    return [
        'classes' => [
            'id' => '',
            'title' => '',
            'level' => '',
            'price_eur' => '',
            'image_url' => '',
            'description' => '',
            'schedules' => [],
        ],
        'events' => [
            'id' => '',
            'title' => '',
            'start_at' => '',
            'end_at' => '',
            'location' => '',
            'maps_url' => '',
            'price_eur' => '',
            'description' => '',
        ],
        'products' => [
            'id' => '',
            'title' => '',
            'category' => '',
            'price_eur' => '',
            'detail' => '',
            'short_description' => '',
            'image_url' => '',
            'image_focus_x' => '50',
            'image_focus_y' => '50',
            'image_zoom' => '100',
            'description' => '',
        ],
        'testimonials' => [
            'id' => '',
            'quote' => '',
            'name' => '',
        ],
    ];
}

function shine_bright_content_section_exists(string $section): bool
{
    return in_array($section, shine_bright_content_sections(), true);
}

function shine_bright_content_section_items(array $content, string $lang, string $section): array
{
    return isset($content[$lang][$section]) && is_array($content[$lang][$section])
        ? array_values(array_filter($content[$lang][$section], 'is_array'))
        : [];
}

function shine_bright_content_fallback_lang(string $lang): string
{
    return $lang === 'en' ? 'bg' : 'en';
}

function shine_bright_public_content_section_items(array $content, string $lang, string $section): array
{
    $primaryItems = shine_bright_content_section_items($content, $lang, $section);
    $fallbackItems = shine_bright_content_section_items($content, shine_bright_content_fallback_lang($lang), $section);
    $items = [];
    $seenIds = [];

    foreach ($primaryItems as $item) {
        $id = trim((string) ($item['id'] ?? ''));
        if ($id !== '') {
            $seenIds[$id] = true;
        }
        $items[] = shine_bright_normalize_content_item($section, $item);
    }

    foreach ($fallbackItems as $item) {
        $id = trim((string) ($item['id'] ?? ''));
        if ($id !== '' && isset($seenIds[$id])) {
            continue;
        }
        if ($id !== '') {
            $seenIds[$id] = true;
        }
        $items[] = shine_bright_normalize_content_item($section, $item);
    }

    return $items;
}

function shine_bright_normalize_content_item(string $section, array $item): array
{
    $templates = shine_bright_content_item_templates();
    $template = $templates[$section] ?? [];
    $normalized = $template;

    foreach ($template as $field => $defaultValue) {
        if ($section === 'classes' && $field === 'schedules') {
            continue;
        }
        $value = $item[$field] ?? $defaultValue;
        $normalized[$field] = is_string($value) ? trim($value) : $defaultValue;
    }

    $seed = $normalized['id'] !== ''
        ? $normalized['id']
        : ($normalized['title'] !== ''
            ? $normalized['title']
            : ($normalized['name'] !== ''
                ? $normalized['name']
                : ($normalized['quote'] !== '' ? $normalized['quote'] : $section)));
    $normalized['id'] = shine_bright_slugify($seed);

    if ($section === 'classes') {
        $normalized['schedules'] = shine_bright_class_schedules($item);
        $primarySchedule = $normalized['schedules'][0] ?? null;
        $normalized['location'] = (string) ($primarySchedule['location'] ?? '');
        $normalized['maps_url'] = (string) ($primarySchedule['maps_url'] ?? '');
        $normalized['start_at'] = '';
        $normalized['end_at'] = '';
    }

    return $normalized;
}

function shine_bright_numeric_string(string|int|float|null $value, int $default, int $min, int $max): string
{
    $number = is_numeric($value) ? (int) round((float) $value) : $default;
    $number = max($min, min($max, $number));
    return (string) $number;
}

function shine_bright_product_media_style(array $product): string
{
    $imageUrl = shine_bright_normalize_public_media_url($product['image_url'] ?? '');
    if ($imageUrl === '') {
        return '';
    }

    $focusX = shine_bright_numeric_string($product['image_focus_x'] ?? null, 50, 0, 100);
    $focusY = shine_bright_numeric_string($product['image_focus_y'] ?? null, 50, 0, 100);
    $zoom = shine_bright_numeric_string($product['image_zoom'] ?? null, 100, 80, 200);

    return "background-image:url('" . htmlspecialchars($imageUrl, ENT_QUOTES) . "');background-position:{$focusX}% {$focusY}%;background-size:{$zoom}% auto;";
}

function shine_bright_upsert_content_item(array &$content, string $lang, string $section, array $item): array
{
    if (!shine_bright_content_section_exists($section)) {
        throw new InvalidArgumentException('Unsupported content section.');
    }

    $normalized = shine_bright_normalize_content_item($section, $item);
    $content[$lang][$section] = shine_bright_content_section_items($content, $lang, $section);

    foreach ($content[$lang][$section] as $index => $existing) {
        if ((string) ($existing['id'] ?? '') !== $normalized['id']) {
            continue;
        }

        $content[$lang][$section][$index] = $normalized;
        return $normalized;
    }

    $content[$lang][$section][] = $normalized;
    return $normalized;
}

function shine_bright_delete_content_item(array &$content, string $lang, string $section, string $id): bool
{
    if (!shine_bright_content_section_exists($section)) {
        return false;
    }

    $items = shine_bright_content_section_items($content, $lang, $section);

    foreach ($items as $index => $item) {
        if ((string) ($item['id'] ?? '') !== $id) {
            continue;
        }

        array_splice($items, $index, 1);
        $content[$lang][$section] = array_values($items);
        return true;
    }

    return false;
}
