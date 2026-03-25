<?php
// archivo: public/convenios/registro_convenio.php
require_once __DIR__ . '/../auth.php';
require_login();
if(($_SESSION['rol']??'')==='observador'){ header("Location: ../index.php"); exit; }
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../header.php';
?>

<div class="layout"><div class="layout-inner">

  <div class="form-header">
    <h1 class="form-title">
      <span class="material-icons">handshake</span>
      Registrar Convenio
    </h1>
    <p class="form-subtitle">Ingreso de convenios institucionales con archivo PDF</p>
  </div>

  <?php if(isset($_GET['msg'])): ?>
    <div class="form-section" style="border-color:#bbf7d0;background:#ecfdf5">
        <div style="display:flex; align-items:center; gap:10px; color:#166534;">
            <span class="material-icons">check_circle</span>
            <strong><?= htmlspecialchars($_GET['msg']) ?></strong>
        </div>
    </div>
  <?php endif; ?>

  <form action="guardarConvenio.php" method="POST" enctype="multipart/form-data" id="formConvenio" class="form-grid">

    <div class="form-section">
      <h3 class="section-title">
        <span class="material-icons">info</span>
        Información del Convenio
      </h3>

      <div class="form-grid">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Nombre del Convenio <span class="required">*</span></label>
            <input type="text" name="nombre" class="form-input" required>
          </div>

          <div class="form-group">
            <label class="form-label">Tipo <span class="required">*</span></label>
            <select name="tipo" class="form-select" required>
              <option value="Académico">Académico</option>
              <option value="Colaboración">Colaboración</option>
              <option value="Práctica Profesional">Práctica Profesional</option>
              <option value="Extensión">Extensión</option>
              <option value="Otro">Otro</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Contraparte <span class="required">*</span></label>
            <input type="text" name="contraparte" class="form-input" required>
          </div>

          <div class="form-group">
            <label class="form-label">Responsable Interno</label>
            <input type="text" name="responsable" class="form-input">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Fecha Inicio <span class="required">*</span></label>
            <input type="date" name="fecha_inicio" class="form-input" required>
          </div>

          <div class="form-group">
            <label class="form-label">Fecha Fin</label>
            <input type="date" name="fecha_fin" class="form-input">
          </div>
        </div>



        <div class="form-group full-width">
          <label class="form-label">Observaciones</label>
          <textarea name="observaciones" rows="3" class="form-textarea"></textarea>
        </div>

        <div class="form-section">
            <h2 class="section-title"><span class="material-icons">attach_file</span>Archivo del Convenio</h2>
            <div class="file-upload-section">
                <span class="material-icons file-upload-icon">picture_as_pdf</span>
                <p>Subir documento PDF del convenio</p>
                <label class="file-upload-label">
                    Seleccionar Archivo
                    <input type="file" name="archivo" accept="application/pdf" class="file-upload-input" onchange="document.getElementById('fileName').textContent = this.files[0].name">
                </label>
                <div id="fileName" class="file-name" style="margin-top:10px;"></div>
            </div>
        </div>
      </div>
    </div>

    <div class="form-actions">
      <a href="ver_convenios.php" class="btn btn-secondary">
        <span class="material-icons">list</span> Ver Convenios
      </a>
      <button type="submit" class="btn btn-primary">
        <span class="material-icons">save</span> Guardar Convenio
      </button>
    </div>
  </form>

</div></div>
<?php require_once __DIR__ . '/../footer.php'; ?>
</body>
</html>
