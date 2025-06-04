<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicationResource\Pages;
use App\Filament\Resources\ApplicationResource\RelationManagers;
use App\Models\Application;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Webbingbrasil\FilamentCopyActions\Tables\Actions\CopyAction;
use Webbingbrasil\FilamentCopyActions\Forms\Actions\CopyAction as FormCopyAction;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static ?string $navigationLabel = 'Applications';
    
    protected static ?string $modelLabel = 'Application';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                    
                TextInput::make('name')
                    ->label('Application Name')
                    ->placeholder('e.g., Google Authenticator, iPhone, Work Phone')
                    ->required()
                    ->maxLength(255),
                    
                TextInput::make('secret')
                    ->label('Secret Key')
                    ->placeholder('Enter your secret key')
                    ->helperText('Secret key - save this securely, you won\'t see it again')
                    ->required()
                    ->maxLength(255)
                    ->visibleOn('create'),
                    
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
                
                Actions::make([
                    FormCopyAction::make('get_current_otp')
                        ->label('Get Current OTP')
                        ->icon('heroicon-o-clock')
                        ->copyable(fn ($record) => $record?->getTotpInstance()?->now() ?? '')
                        ->action(function ($record) {
                            if ($record) {
                                // Generate fresh OTP for copying
                                $totp = $record->getTotpInstance();
                                $currentOtp = $totp->now();
                                
                                // Calculate expiration time
                                $period = 30; // Default TOTP period is 30 seconds
                                $currentTime = time();
                                $timeInPeriod = $currentTime % $period;
                                $expiresIn = $period - $timeInPeriod;
                                $expiresAt = $currentTime + $expiresIn;
                                
                                // Add additional notification with OTP details (after the default "Copied!" message)
                                Notification::make()
                                    ->title("OTP: {$currentOtp}")
                                    ->body("Expires in: {$expiresIn} seconds\nExpires at: " . date('H:i:s', $expiresAt))
                                    ->info()
                                    ->duration(5000)
                                    ->send();
                                
                                return $currentOtp;
                            }
                            return '';
                        })
                        ->visible(fn ($record) => $record !== null && $record->secret),
                        
                    Action::make('test_verification')
                        ->label('Test OTP')
                        ->icon('heroicon-o-shield-check')
                        ->form([
                            TextInput::make('otp_code')
                                ->label('Enter OTP Code')
                                ->required()
                                ->length(6)
                                ->numeric()
                        ])
                        ->action(function ($record, array $data) {
                            if ($record) {
                                $isValid = $record->verifyOtp($data['otp_code']);
                                
                                if ($isValid) {
                                    Notification::make()
                                        ->title('OTP Verification Successful')
                                        ->body('The OTP code is valid!')
                                        ->success()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('OTP Verification Failed')
                                        ->body('The OTP code is invalid or expired.')
                                        ->danger()
                                        ->send();
                                }
                            }
                        })
                        ->visible(fn ($record) => $record !== null && $record->secret),

                ])
                ->visible(fn ($record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Application Name')
                    ->searchable()
                    ->sortable(),
                    
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                    
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                CopyAction::make('get_otp')
                    ->label('Get OTP')
                    ->icon('heroicon-o-clock')
                    ->copyable(fn ($record) => $record->getTotpInstance()->now())
                    ->action(function ($record) {
                        // Generate fresh OTP for copying
                        $totp = $record->getTotpInstance();
                        $currentOtp = $totp->now();
                        
                        // Calculate expiration time
                        $period = 30; // Default TOTP period is 30 seconds
                        $currentTime = time();
                        $timeInPeriod = $currentTime % $period;
                        $expiresIn = $period - $timeInPeriod;
                        $expiresAt = $currentTime + $expiresIn;
                        
                        // Add additional notification with OTP details (after the default "Copied!" message)
                        Notification::make()
                            ->title("OTP: {$currentOtp}")
                            ->body("Expires in: {$expiresIn} seconds\nExpires at: " . date('H:i:s', $expiresAt))
                            ->info()
                            ->duration(5000)
                            ->send();
                        
                        return $currentOtp;
                    })
                    ->visible(fn ($record) => $record->secret && $record->is_active),
                Tables\Actions\Action::make('test_otp')
                    ->label('Test OTP')
                    ->icon('heroicon-o-shield-check')
                    ->form([
                        TextInput::make('otp_code')
                            ->label('Enter OTP Code')
                            ->required()
                            ->length(6)
                            ->numeric()
                    ])
                    ->action(function ($record, array $data) {
                        $isValid = $record->verifyOtp($data['otp_code']);
                        
                        if ($isValid) {
                            Notification::make()
                                ->title('OTP Verification Successful')
                                ->body('The OTP code is valid!')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('OTP Verification Failed') 
                                ->body('The OTP code is invalid or expired.')
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => $record->secret && $record->is_active),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'create' => Pages\CreateApplication::route('/create'),
            'edit' => Pages\EditApplication::route('/{record}/edit'),
        ];
    }
}
