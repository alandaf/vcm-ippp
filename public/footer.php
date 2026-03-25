<footer class="site-footer">
  <div class="footer-shell">
    <div class="footer-left">
      <img src="/vcm/img/logo_IPP_PNG.png" alt="Logo IPP" class="footer-logo">
      <div class="footer-text">
        <strong>I.P. Escuela de Marina Mercante Piloto Pardo</strong><br>
        <small>Plataforma de Vinculación con el Medio</small>
      </div>
    </div>

    <div class="footer-right">
      <a href="https://www.ippilotopardo.cl" target="_blank">Sitio Institucional</a>
      <a href="https://www.aulavirtualippilotopardo.cl" target="_blank">Aula Virtual cursos Especiales</a>
      <a href="https://ippilotopardo.cl/aulavirtual/" target="_blank">Aula Virtual cursos Marítimos</a>
      <a href="<?= APP_BASE ?>/index.php">Inicio</a>
    </div>
  </div>

  <div class="footer-copy">
    © <?= date('Y') ?> Desarrollado por <strong>Andrés Landa Figueroa</strong> · Todos los derechos reservados.
  </div>
</footer>

<style>
/* ======== FOOTER INSTITUCIONAL (Estilo PGD) ======== */
html, body { height: 100%; }
body { display: flex; flex-direction: column; }

.site-footer {
  margin-top: auto;
  background: #0b1d3a;
  color: #d9e2f2;
  border-top: 4px solid #facc15;
  font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
}
.footer-shell {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem 1.25rem;
}
.footer-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.footer-logo {
  height: 42px;
  width: auto;
}
.footer-text strong {
  color: #fff;
  font-size: 15px;
}
.footer-text small {
  color: #cbd5e1;
  font-size: 12px;
}
.footer-right {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}
.footer-right a {
  color: #d9e2f2;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.2s ease;
  font-size: 0.9rem;
}
.footer-right a:hover {
  color: #facc15;
}
.footer-copy {
  text-align: center;
  font-size: 0.85rem;
  color: #9fb3d6;
  padding: 0.8rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  background: #08162e; /* Fondo más oscuro igual que PGD */
}

/* ======== RESPONSIVE ======== */
@media (max-width: 768px) {
  .footer-shell {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }
  .footer-right {
    justify-content: center;
  }
}
</style>
