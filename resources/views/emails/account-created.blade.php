<div style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.5;">
    <h1 style="font-size: 22px; margin: 0 0 14px;">Welcome to Copier Monitor</h1>
    <p>Your company account for <strong>{{ $company->name }}</strong> has been created.</p>
    <p>You can sign in using these details:</p>
    <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; padding: 14px; margin: 18px 0;">
        <p style="margin: 0 0 8px;"><strong>Login:</strong> <a href="{{ $loginUrl }}">{{ $loginUrl }}</a></p>
        <p style="margin: 0 0 8px;"><strong>Email:</strong> {{ $admin->email }}</p>
        <p style="margin: 0;"><strong>Temporary password:</strong> {{ $temporaryPassword }}</p>
    </div>
    <p>Please change the password after first login.</p>
</div>
