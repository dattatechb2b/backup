<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Orcamento;

class AuthController extends Controller
{
    /**
     * Mostra a página de login
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Processa o login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        // Permitir login com email OU nome
        $loginField = filter_var($request->email, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        $credentials = [
            $loginField => $request->email,
            'password' => $request->password
        ];

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ])->onlyInput('email');
    }

    /**
     * Dashboard principal
     */
    public function dashboard()
    {
        // Obter tenant_id da sessão (injetado pelo ProxyAuth)
        // BANCO EXCLUSIVO POR TENANT - Não precisa filtrar por tenant_id
        // Todos os dados no banco já são do tenant correto

        // Buscar orçamentos pendentes reais (últimos 5)
        $orcamentosPendentes = Orcamento::where('status', 'pendente')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Buscar orçamentos realizados reais (últimos 5)
        $orcamentosRealizados = Orcamento::where('status', 'realizado')
            ->orderBy('data_conclusao', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'user' => Auth::user(),
            'orcamentosPendentes' => $orcamentosPendentes,
            'orcamentosRealizados' => $orcamentosRealizados
        ]);
    }

    /**
     * Efetua logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}