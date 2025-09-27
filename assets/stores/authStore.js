import { defineStore } from 'pinia';
import { refreshTokenApi, logoutApi } from '../services/authService';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: (()=>{ try { const v = localStorage.getItem('auth_user'); return v?JSON.parse(v):null;} catch(_){ return null; } })(),
    token: (()=>{ try { return localStorage.getItem('auth_token'); } catch(_){ return null; } })(),
    tokenType: (()=>{ try { return localStorage.getItem('auth_token_type'); } catch(_){ return null; } })(),
    expiresAt: (()=>{ try { return localStorage.getItem('auth_expires_at'); } catch(_){ return null; } })(),
    issuedAt: (()=>{ try { return localStorage.getItem('auth_issued_at'); } catch(_){ return null; } })(),
    loading: false,
    error: ''
  }),
  getters: {
    isAuthenticated: s => !!s.token,
    displayName: s => s.user?.name || s.user?.email || '',
    expiresInMs: s => s.expiresAt ? (new Date(s.expiresAt).getTime() - Date.now()) : null,
    isExpired: s => s.expiresAt ? Date.now() >= new Date(s.expiresAt).getTime() : true,
  },
  actions: {
    setUser(u){ this.user = u; try { localStorage.setItem('auth_user', JSON.stringify(u)); } catch(_){} },
    setToken(t){ this.token = t; if (t) { try { localStorage.setItem('auth_token', t); } catch(_){} } else { try { localStorage.removeItem('auth_token'); } catch(_){} } },
    setTokenType(tt){ this.tokenType = tt; if (tt){ try { localStorage.setItem('auth_token_type', tt);} catch(_){} } },
    setExpiresAt(v){ this.expiresAt = v; if (v){ try { localStorage.setItem('auth_expires_at', v);} catch(_){} } },
    setIssuedAt(v){ this.issuedAt = v; if (v){ try { localStorage.setItem('auth_issued_at', v);} catch(_){} } },
    setLoading(v){ this.loading = v; },
    setError(e){ this.error = e||''; },
    initializeSession(payload){
      if (payload.user) this.setUser(payload.user);
      if (payload.token) this.setToken(payload.token);
      if (payload.token_type) this.setTokenType(payload.token_type);
      if (payload.expires_at) this.setExpiresAt(payload.expires_at);
      if (payload.issued_at) this.setIssuedAt(payload.issued_at);
    },
    async silentRefresh(){
      try {
          const data = await refreshTokenApi(); this.initializeSession(data);
      }
      catch { /* si falla, no forzamos logout inmediato; se hará al primer 401 */ }
    },
    async logout(){
      try { await logoutApi(); } catch(_){}
      this.user=null; this.setToken(null); this.tokenType=null; this.expiresAt=null; this.issuedAt=null;
      try { localStorage.removeItem('auth_user'); localStorage.removeItem('auth_token_type'); localStorage.removeItem('auth_expires_at'); localStorage.removeItem('auth_issued_at'); } catch(_){}
    },
    async bootstrap(){
      // Intento de refresh silencioso inicial si no tengo access token válido
      if (!this.token || this.isExpired) {
        await this.silentRefresh();
      }
    }
  }
});
