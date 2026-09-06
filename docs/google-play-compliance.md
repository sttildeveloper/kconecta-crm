# Preparación de cumplimiento Google Play

## Decisiones seguras aplicadas

- La eliminación de cuenta retira inmediatamente el contenido público del proveedor, relaciones comerciales, dirección, especialidades, medios físicos exclusivos, tokens, sesiones y métricas.
- Los archivos se mueven a staging antes de la transacción: un rollback restaura los archivos. Las rutas absolutas, traversal y archivos compartidos se rechazan.
- Valoraciones, códigos de trabajo, tickets, mensajes y auditorías permanecen en `retain` hasta decisión explícita. Solo se borran con una política de entorno igual a `delete`.
- La aceptación legal en registros nuevos está preparada pero desactivada hasta una publicación móvil coordinada.
- Consentimiento web versionado por categorías: necesarias, analítica y publicidad. AdSense solo se carga tras consentimiento de publicidad.
- Logs diarios; métricas, IP, tickets y auditorías tienen plazos independientes y desactivados mientras no exista decisión.
- Los comandos de retención son `compliance:retention-inventory` y `compliance:retention-prune`; el segundo es dry-run salvo `--apply`. Ninguno elimina backups.

## Retención acordada de backups

- Automáticos Hostinger: máximo 14 días.
- Manuales rutinarios: 30 días.
- Excepcionales de migración: 90 días.

## Datos que JM debe proporcionar antes de publicar textos definitivos

- Nombre o razón social legal del responsable.
- NIF/CIF.
- Domicilio legal.
- Ubicación contractual de infraestructura y posibles transferencias.
- Jurisdicción/ley aplicable, si procede indicarla.
- Versiones y fecha efectiva de términos y privacidad.
- Plazos aprobados para métricas, IP, tickets y auditorías.
- Política definitiva de valoraciones, códigos de trabajo, tickets, mensajes y auditorías tras eliminar una cuenta.

No se ha definido ni inferido nombre legal, NIF, domicilio, edad mínima o jurisdicción.
