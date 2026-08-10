<?php

declare(strict_types=1);

namespace App\Enums;

enum QuotationStatus: string
{
    case Draft = 'draft';
    case Ready = 'ready';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case RevisionRequested = 'revision_requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Converted = 'converted';
    case Cancelled = 'cancelled';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Ready, self::Cancelled],
            self::Ready => [self::Draft, self::Sent, self::Cancelled],
            self::Sent => [self::Viewed, self::RevisionRequested, self::Approved, self::Rejected, self::Expired, self::Cancelled],
            self::Viewed => [self::RevisionRequested, self::Approved, self::Rejected, self::Expired, self::Cancelled],
            self::RevisionRequested => [self::Draft, self::Cancelled],
            self::Approved => [self::Converted, self::Cancelled],
            self::Rejected, self::Expired => [self::Draft, self::Cancelled],
            self::Converted, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }
}
