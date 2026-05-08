<?php
require_once dirname(__DIR__) . '/content.php';

shine_bright_require_api_token();

$method = shine_bright_api_request_method();
$content = shine_bright_load_content();
$reservations = shine_bright_load_reservations();

if ($method === 'GET') {
    $id = isset($_GET['id']) && is_string($_GET['id']) ? trim($_GET['id']) : '';
    $classId = isset($_GET['class_id']) && is_string($_GET['class_id']) ? trim($_GET['class_id']) : '';
    $status = isset($_GET['status']) && is_string($_GET['status']) ? trim($_GET['status']) : '';
    $attendance = isset($_GET['attendance']) && is_string($_GET['attendance']) ? trim($_GET['attendance']) : '';

    $filtered = array_values(array_filter($reservations, static function (array $reservation) use ($id, $classId, $status, $attendance): bool {
        if ($id !== '' && (string) ($reservation['id'] ?? '') !== $id) {
            return false;
        }
        if ($classId !== '' && (string) ($reservation['class_id'] ?? '') !== $classId) {
            return false;
        }
        if ($status !== '' && (string) ($reservation['status'] ?? '') !== $status) {
            return false;
        }
        if ($attendance !== '' && (string) ($reservation['attendance'] ?? '') !== $attendance) {
            return false;
        }
        return true;
    }));

    if ($id !== '') {
        if (!$filtered) {
            shine_bright_api_json_response(['ok' => false, 'error' => 'Reservation not found.'], 404);
        }

        shine_bright_api_json_response([
            'ok' => true,
            'reservation' => $filtered[0],
        ]);
    }

    $counts = [
        'new' => 0,
        'confirmed' => 0,
        'waitlisted' => 0,
        'cancelled' => 0,
        'attended' => 0,
        'no_show' => 0,
    ];

    foreach ($filtered as $reservation) {
        $reservationStatus = (string) ($reservation['status'] ?? '');
        $reservationAttendance = (string) ($reservation['attendance'] ?? '');
        if (isset($counts[$reservationStatus])) {
            $counts[$reservationStatus]++;
        }
        if ($reservationAttendance === 'attended') {
            $counts['attended']++;
        }
        if ($reservationAttendance === 'no-show') {
            $counts['no_show']++;
        }
    }

    shine_bright_api_json_response([
        'ok' => true,
        'reservations' => $filtered,
        'counts' => $counts,
    ]);
}

if ($method !== 'PATCH') {
    shine_bright_api_json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$body = shine_bright_api_read_json_body();
$reservationId = trim((string) ($body['id'] ?? ''));
if ($reservationId === '') {
    shine_bright_api_json_response(['ok' => false, 'error' => 'Reservation id is required.'], 422);
}

$allowedStatuses = ['new', 'confirmed', 'waitlisted', 'cancelled'];
$allowedAttendance = ['pending', 'attended', 'no-show'];
$mailOutcome = '';
$updatedReservation = shine_bright_update_reservation_record($reservations, $reservationId, function (array $reservation) use ($body, $content, &$mailOutcome, $allowedStatuses, $allowedAttendance): array {
    $previousStatus = (string) ($reservation['status'] ?? 'new');
    $newStatus = isset($body['status']) && in_array((string) $body['status'], $allowedStatuses, true)
        ? (string) $body['status']
        : $previousStatus;
    $newAttendance = isset($body['attendance']) && in_array((string) $body['attendance'], $allowedAttendance, true)
        ? (string) $body['attendance']
        : (string) ($reservation['attendance'] ?? 'pending');
    $adminNote = isset($body['admin_note']) && is_scalar($body['admin_note'])
        ? trim((string) $body['admin_note'])
        : (string) ($reservation['admin_note'] ?? '');

    $reservation['status'] = $newStatus;
    $reservation['attendance'] = $newAttendance;
    $reservation['admin_note'] = $adminNote;
    $reservation['updated_at'] = gmdate('c');

    $reservationLang = in_array(($reservation['lang'] ?? 'bg'), ['bg', 'en'], true) ? (string) $reservation['lang'] : 'bg';
    $brand = $content[$reservationLang]['brand'] ?? ($content['bg']['brand'] ?? []);
    $guestEmail = trim((string) ($reservation['email'] ?? ''));
    $shouldSendStatusEmail = $newStatus !== $previousStatus
        && in_array($newStatus, ['confirmed', 'waitlisted', 'cancelled'], true)
        && $guestEmail !== '';

    if (!$shouldSendStatusEmail) {
        if ($newStatus !== $previousStatus && $guestEmail === '') {
            $mailOutcome = 'Reservation updated without status email because no guest email is present.';
        }
        return $reservation;
    }

    $emailPayload = shine_bright_compose_reservation_email(
        $newStatus,
        $reservationLang,
        $brand,
        $reservation,
        $adminNote
    );

    $mailSent = shine_bright_send_text_mail(
        $guestEmail,
        $emailPayload['subject'],
        $emailPayload['body'],
        (string) ($brand['contact_email'] ?? '')
    );

    if ($mailSent) {
        $reservation['status_email_sent_at'] = gmdate('c');
        $reservation['status_email_last'] = $newStatus;
        $mailOutcome = ucfirst($newStatus) . ' email sent.';
    } else {
        $mailOutcome = 'Status updated, but the status email could not be sent.';
    }

    return $reservation;
});

if (!$updatedReservation) {
    shine_bright_api_json_response(['ok' => false, 'error' => 'Reservation not found.'], 404);
}

shine_bright_save_reservations($reservations);

shine_bright_api_json_response([
    'ok' => true,
    'reservation' => $updatedReservation,
    'mail' => $mailOutcome,
]);
