document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('recommendation-form');
  const interestsInput = document.getElementById('interests');
  const tipoSelect = document.getElementById('tipo');
  const resultsDiv = document.getElementById('results');
  const loadingDiv = document.getElementById('loading');
  const darkModeToggle = document.getElementById('darkModeToggle');

  // Toggle modo oscuro
  darkModeToggle.addEventListener('change', () => {
    document.body.classList.toggle('dark-mode');
  });

  // Buscar recomendaciones según intereses
  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const interests = interestsInput.value.trim();
    const tipo = tipoSelect.value;

    if (!interests) {
      Swal.fire({
        icon: 'warning',
        title: 'Oops...',
        text: 'Por favor ingresa tus intereses antes de buscar.',
        showClass: { popup: 'animate__animated animate__fadeInDown' },
        hideClass: { popup: 'animate__animated animate__fadeOutUp' }
      });
      return;
    }

    resultsDiv.innerHTML = '';
    loadingDiv.classList.remove('d-none');

    try {
      const response = await fetch('recommend.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ interests: interests, tipo: tipo })
      });

      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const recommendations = await response.json();
      loadingDiv.classList.add('d-none');

      if (recommendations.error) {
        Swal.fire({
          icon: 'error',
          title: 'Sin resultados',
          text: recommendations.error
        });
      } else if (recommendations.length === 0) {
        Swal.fire({
          icon: 'info',
          title: 'Sin resultados',
          text: 'No se encontraron recomendaciones para esos intereses.'
        });
      } else {
        // Mostrar alerta de éxito
        Swal.fire({
          icon: 'success',
          title: '¡Tenemos recomendaciones!',
          text: 'Revisa las opciones que encontramos para ti 😃',
          timer: 2000,
          showConfirmButton: false
        });

        // Renderizar recomendaciones
        recommendations.forEach(item => {
          const col = document.createElement('div');
          col.classList.add('col');
          col.setAttribute("data-aos", "zoom-in");
          col.innerHTML = `
            <div class="recommendation-card h-100">
              <img src="${item.portada_url || 'https://via.placeholder.com/200x300?text=Sin+Imagen'}" 
                   alt="${item.titulo}">
              <h5 class="mt-2">${item.titulo}</h5>
              <p class="text-muted">${item.sinopsis}</p>
            </div>
          `;
          resultsDiv.appendChild(col);
        });

        // refrescar animaciones AOS
        AOS.refresh();
      }
    } catch (error) {
      loadingDiv.classList.add('d-none');
      console.error('Error al obtener recomendaciones:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error de conexión',
        text: 'No pudimos cargar las recomendaciones. Inténtalo más tarde.'
      });
    }
  });

  // 🔹 Cargar Top 10 películas de la semana (TMDb)
  async function loadTopMovies() {
    try {
      const response = await fetch('recommend.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ top: "peliculas" })
      });

      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const movies = await response.json();
      const topMoviesDiv = document.getElementById('top-movies');
      topMoviesDiv.innerHTML = "";

      movies.forEach((movie, index) => {
        const col = document.createElement('div');
        col.classList.add('col');
        col.setAttribute("data-aos", "fade-up");
        col.setAttribute("data-aos-delay", index * 100);

        col.innerHTML = `
          <div class="recommendation-card h-100 position-relative">
            <span class="badge bg-warning text-dark position-absolute m-2">#${index + 1}</span>
            <img src="${movie.portada_url || 'https://via.placeholder.com/200x300?text=Sin+Imagen'}" 
                 alt="${movie.titulo}">
            <h6 class="mt-2">${movie.titulo}</h6>
            <p class="small">${movie.descripcion}</p>
          </div>
        `;
        topMoviesDiv.appendChild(col);
      });

      AOS.refresh();
    } catch (error) {
      console.error("Error cargando Top 10:", error);
    }
  }

  // Ejecutar al cargar la página
  loadTopMovies();
});
