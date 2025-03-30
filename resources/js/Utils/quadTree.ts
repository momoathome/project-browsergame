export class Quadtree {
  boundary: { x: number; y: number; width: number; height: number };
  capacity: number;
  points: Array<{ x: number; y: number; data: any }>;
  divided: boolean;
  northeast?: Quadtree;
  northwest?: Quadtree;
  southeast?: Quadtree;
  southwest?: Quadtree;

  constructor(boundary: { x: number; y: number; width: number; height: number }, capacity = 4) {
    this.boundary = boundary;
    this.capacity = capacity;
    this.points = [];
    this.divided = false;
  }

  subdivide() {
    const x = this.boundary.x;
    const y = this.boundary.y;
    const w = this.boundary.width / 2;
    const h = this.boundary.height / 2;

    const ne = { x: x + w, y: y - h, width: w, height: h };
    const nw = { x: x - w, y: y - h, width: w, height: h };
    const se = { x: x + w, y: y + h, width: w, height: h };
    const sw = { x: x - w, y: y + h, width: w, height: h };

    this.northeast = new Quadtree(ne, this.capacity);
    this.northwest = new Quadtree(nw, this.capacity);
    this.southeast = new Quadtree(se, this.capacity);
    this.southwest = new Quadtree(sw, this.capacity);
    this.divided = true;
  }

  insert(point: { x: number; y: number; data: any }): boolean {
    if (!this.contains(point)) {
      return false;
    }

    if (this.points.length < this.capacity) {
      this.points.push(point);
      return true;
    }

    if (!this.divided) {
      this.subdivide();
    }

    return (
      this.northeast!.insert(point) ||
      this.northwest!.insert(point) ||
      this.southeast!.insert(point) ||
      this.southwest!.insert(point)
    );
  }

  query(range: { x: number; y: number; width: number; height: number }, found: Array<{ x: number; y: number; data: any }> = []) {
    if (!this.intersects(range)) {
      return found;
    }

    for (const p of this.points) {
      if (
        p.x >= range.x - range.width &&
        p.x <= range.x + range.width &&
        p.y >= range.y - range.height &&
        p.y <= range.y + range.height
      ) {
        found.push(p);
      }
    }

    if (this.divided) {
      this.northeast!.query(range, found);
      this.northwest!.query(range, found);
      this.southeast!.query(range, found);
      this.southwest!.query(range, found);
    }

    return found;
  }

  contains(point: { x: number; y: number }) {
    return (
      point.x >= this.boundary.x - this.boundary.width &&
      point.x <= this.boundary.x + this.boundary.width &&
      point.y >= this.boundary.y - this.boundary.height &&
      point.y <= this.boundary.y + this.boundary.height
    );
  }

  intersects(range: { x: number; y: number; width: number; height: number }) {
    return !(
      range.x - range.width > this.boundary.x + this.boundary.width ||
      range.x + range.width < this.boundary.x - this.boundary.width ||
      range.y - range.height > this.boundary.y + this.boundary.height ||
      range.y + range.height < this.boundary.y - this.boundary.height
    );
  }
}
