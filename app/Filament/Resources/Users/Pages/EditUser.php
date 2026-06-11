<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetProgress')
                ->label('Reset Progress')
                ->color('danger')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading('Reset Student Progression?')
                ->modalDescription('This will completely wipe out this student\'s level, XP, coins, lesson submissions, block submissions, earned achievements, inventory, equipped items, and world completion certificates back to baseline defaults. This action is destructive and irreversible.')
                ->visible(fn () => $this->record->role === 'student')
                ->action(function (): void {
                    activity()
                        ->performedOn($this->record)
                        ->causedBy(auth()->user())
                        ->event('admin.reset')
                        ->withChanges([
                            'attribute' => [
                                'level' => '1',
                                'xp' => '0',
                                'coins' => '0',
                            ],
                            'old' => [
                                'level' => $this->record->level,
                                'xp' => $this->record->xp,
                                'coins' => $this->record->coins,
                            ],
                        ])
                        ->log('Admin reset student progress. Level, XP, coins, lesson and block submissions, achievements, inventory, equipped items, and world completion certificates have been wiped.');

                    $this->record->lessonSubmissions()->delete();
                    $this->record->blockSubmissions()->delete();
                    $this->record->achievements()->detach();
                    $this->record->inventory()->delete();
                    $this->record->worldCompletions()->delete();

                    $prefs = $this->record->preferences ?? [];
                    $prefs['equipped_title'] = null;
                    $prefs['equipped_avatar'] = null;
                    $this->record->update(['preferences' => $prefs]);

                    $this->record->updateQuietly([
                        'level' => 1,
                        'xp' => 0,
                        'coins' => 0,
                    ]);

                    $this->refreshFormData([
                        'level',
                        'xp',
                        'coins',
                    ]);

                    Notification::make()
                        ->title('Student progress has been successfully reset!')
                        ->success()
                        ->send();
                }),

            DeleteAction::make(),
        ];
    }
}
