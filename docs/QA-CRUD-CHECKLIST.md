# QA CRUD Checklist

Matriz de verificación manual antes de deploy.

| Módulo | admin | editor | program_head |
|--------|-------|--------|--------------|
| Login/Logout | ✓ | ✓ | ✓ |
| Dashboard | ✓ | ✓ | ✓ |
| Académico CRUD | ✓ | ✓ | solo su área |
| Centro CRUD | ✓ | ✓ | solo su sector |
| General CRUD | ✓ | ✓ | lectura |
| Medios | ✓ | ✓ | según área |
| Usuarios | ✓ | ✗ | ✗ |
| Config/Colores | ✓ | ✗ | ✗ |
| TV polling | público | público | público |

## Comandos de prueba

```bash
composer test
cd frontend && npm test && npm run test:e2e
```
