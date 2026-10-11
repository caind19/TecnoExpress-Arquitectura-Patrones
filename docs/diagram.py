"""
Genera el diagrama de arquitectura del proyecto:
 - Microservicios (broker, order, inventory, billing) y su flujo Pub-Sub
 - Un detalle de la Arquitectura Hexagonal dentro de cada microservicio
"""
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
from matplotlib.patches import FancyBboxPatch, FancyArrowPatch, Circle
from matplotlib.lines import Line2D

fig, (ax1, ax2) = plt.subplots(2, 1, figsize=(11, 11.5), gridspec_kw={"height_ratios": [1, 1.3], "hspace": 0.05})

# ============================================================
# Panel 1: Microservicios + Publisher-Subscriber
# ============================================================
ax1.set_xlim(0, 10)
ax1.set_ylim(1.2, 6)
ax1.axis("off")
ax1.set_title("TecnoExpress SAS — Microservicios + Publisher-Subscriber", fontsize=14, fontweight="bold")

def box(ax, x, y, w, h, text, color, fontsize=10, fontweight="bold"):
    b = FancyBboxPatch((x, y), w, h, boxstyle="round,pad=0.08",
                        linewidth=1.6, edgecolor="#333333", facecolor=color)
    ax.add_patch(b)
    ax.text(x + w / 2, y + h / 2, text, ha="center", va="center",
            fontsize=fontsize, fontweight=fontweight, color="#1a1a1a", wrap=True)

# Broker en el centro
box(ax1, 4.0, 2.4, 2.0, 1.2, "BROKER\n(Pub-Sub)\nPOST /publish\nPOST /subscribe", "#ffe0b2", 9)

# Microservicios alrededor
box(ax1, 0.4, 4.3, 2.2, 1.1, "order-service\n:4001\n(Hexagonal)", "#bbdefb", 9)
box(ax1, 3.9, 4.3, 2.2, 1.1, "inventory-service\n:4002\n(Hexagonal)", "#c8e6c9", 9)
box(ax1, 7.4, 4.3, 2.2, 1.1, "billing-service\n:4003\n(Hexagonal)", "#fff9c4", 9)

def arrow(ax, p1, p2, text, color="#555555", rad=0.0):
    a = FancyArrowPatch(p1, p2, arrowstyle="-|>", mutation_scale=16,
                         color=color, linewidth=1.6, connectionstyle=f"arc3,rad={rad}")
    ax.add_patch(a)
    mx, my = (p1[0] + p2[0]) / 2, (p1[1] + p2[1]) / 2
    ax.text(mx, my + 0.12, text, ha="center", fontsize=7.5, color=color)

arrow(ax1, (1.6, 4.35), (4.35, 3.45), "", "#1565c0")
ax1.text(2.6, 4.15, 'publica\n"pedido.creado"', ha="center", fontsize=7.5, color="#1565c0")

arrow(ax1, (4.7, 3.6), (4.55, 4.3), "", "#1565c0", rad=0.45)
ax1.text(3.55, 3.55, "webhook\n/events", ha="center", fontsize=7.5, color="#1565c0")

arrow(ax1, (5.3, 4.3), (5.6, 3.6), "", "#2e7d32", rad=-0.2)
ax1.text(6.35, 4.15, 'publica\n"inventario.\ndescontado"', ha="center", fontsize=7.5, color="#2e7d32")

arrow(ax1, (6.0, 3.55), (7.8, 4.3), "", "#2e7d32", rad=-0.35)
ax1.text(7.55, 3.55, "webhook\n/events", ha="center", fontsize=7.5, color="#2e7d32")

ax1.text(5.0, 1.7, "Ningún microservicio conoce la URL de otro: todos se comunican\n"
                    "SOLO a través del broker (acoplamiento mínimo / Publisher-Subscriber)",
         ha="center", fontsize=9, style="italic", color="#444444")

# ============================================================
# Panel 2: Arquitectura Hexagonal + DI (detalle de order-service)
# ============================================================
ax2.set_xlim(0, 10)
ax2.set_ylim(0, 8)
ax2.axis("off")
ax2.set_title("Detalle: Arquitectura Hexagonal + Inyección de Dependencias (order-service)",
              fontsize=13, fontweight="bold")

# Hexágono exterior (infraestructura)
hex_outer = plt.Polygon([(1, 6.8), (5, 7.6), (9, 6.8), (9, 1.4), (5, 0.6), (1, 1.4)],
                         closed=True, facecolor="#f3e5f5", edgecolor="#6a1b9a", linewidth=2)
ax2.add_patch(hex_outer)
ax2.text(5, 7.1, "INFRAESTRUCTURA (adaptadores)", ha="center", fontsize=10, fontweight="bold", color="#6a1b9a")

# Hexágono interior (dominio+aplicación)
hex_inner = plt.Polygon([(2.6, 5.6), (5, 6.1), (7.4, 5.6), (7.4, 2.6), (5, 2.1), (2.6, 2.6)],
                         closed=True, facecolor="#e3f2fd", edgecolor="#1565c0", linewidth=2)
ax2.add_patch(hex_inner)
ax2.text(5, 5.75, "APLICACIÓN + DOMINIO (núcleo)", ha="center", fontsize=9.5, fontweight="bold", color="#1565c0")

box(ax2, 3.5, 4.5, 3.0, 0.9, "CreateOrderUseCase\n(recibe puertos por\nconstructor -> DI)", "#ffffff", 8)
box(ax2, 3.9, 3.0, 2.2, 0.9, "Order (entidad)", "#ffffff", 8.5)

box(ax2, 1.2, 4.6, 2.0, 0.8, "Puerto:\nOrderRepository", "#e1bee7", 7.8)
box(ax2, 1.2, 3.2, 2.0, 0.8, "Puerto:\nEventPublisher", "#e1bee7", 7.8)

box(ax2, 1.2, 6.9, 2.3, 0.9, "Adaptador entrada:\nOrderController (HTTP)", "#c5e1a5", 7.8)
box(ax2, 7.0, 6.9, 2.3, 0.9, "Composition root:\npublic/index.php (inyecta DI)", "#ffccbc", 7.8)
box(ax2, 1.2, 1.6, 2.6, 0.9, "Adaptador salida:\nJsonFileOrderRepository", "#c5e1a5", 7.6)
box(ax2, 6.2, 1.6, 2.6, 0.9, "Adaptador salida:\nHttpEventPublisher ->\nbroker /publish", "#c5e1a5", 7.6)

arrow(ax2, (2.3, 7.35), (4.0, 5.4), "HTTP", "#33691e")
arrow(ax2, (8.1, 6.9), (6.5, 5.4), "inyecta\n(DI)", "#bf360c")
arrow(ax2, (2.5, 1.9), (3.9, 3.3), "implementa", "#33691e", rad=-0.2)
arrow(ax2, (7.0, 1.9), (5.9, 3.3), "implementa", "#33691e", rad=0.2)

ax2.text(5, 0.1, "El dominio depende de PUERTOS (abstracciones); los ADAPTADORES los implementan.\n"
                 "El composition root construye los adaptadores concretos e inyecta las dependencias (DI).",
         ha="center", fontsize=9, style="italic", color="#444444")

plt.tight_layout()
plt.savefig("/home/claude/tecnoexpress-arch/docs/arquitectura_patrones.png", dpi=160, bbox_inches="tight")
print("Diagrama generado.")
