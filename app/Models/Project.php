<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\ProjectManagement\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain ProjectManagement
 */
#[Fillable([
    'organization_id', 'project_type_id', 'client_id', 'ppk_contact_id',
    'code', 'name', 'unit_work', 'project_manager_name', 'project_manager_personnel_id',
    'start_date', 'end_date', 'duration_days', 'status',
    'description', 'notes', 'created_by',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => ProjectStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<ProjectType, $this>
     */
    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function ppkContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'ppk_contact_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasOne<Contract, $this>
     */
    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class);
    }

    /**
     * @return BelongsTo<Personnel, $this>
     */
    public function projectManagerPersonnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'project_manager_personnel_id');
    }

    /**
     * @return HasMany<PersonnelAssignment, $this>
     */
    public function personnelAssignments(): HasMany
    {
        return $this->hasMany(PersonnelAssignment::class);
    }

    /**
     * @return HasMany<CostItem, $this>
     */
    public function costItems(): HasMany
    {
        return $this->hasMany(CostItem::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<ProjectChecklistItem, $this>
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(ProjectChecklistItem::class);
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
